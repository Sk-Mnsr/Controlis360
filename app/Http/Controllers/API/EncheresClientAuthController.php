<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\EncheresClientOtpMail;
use App\Models\EncheresClient;
use App\Models\EncheresClientOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EncheresClientAuthController extends Controller
{
    public function requestCode(Request $request): JsonResponse
    {
        $email = $this->emailFrom($request);
        if ($email instanceof JsonResponse) {
            return $email;
        }

        $existing = EncheresClientOtp::query()->where('email', $email)->first();
        if ($existing && $existing->sent_at && $existing->sent_at->gt(now()->subSeconds(60))) {
            return response()->json([
                'message' => 'Un code vient d’être envoyé. Patientez une minute avant d’en demander un autre.',
            ], 429);
        }

        $code = (string) random_int(100000, 999999);

        EncheresClientOtp::query()->updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'sent_at' => now(),
            ],
        );

        try {
            Mail::to($email)->send(new EncheresClientOtpMail($email, $code));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Le code n’a pas pu être envoyé. Réessayez dans un instant.',
            ], 500);
        }

        return response()->json([
            'message' => 'Un code a été envoyé à votre adresse e-mail.',
            'email' => $email,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $email = $this->emailFrom($request);
        if ($email instanceof JsonResponse) {
            return $email;
        }

        $code = preg_replace('/\D+/', '', (string) $request->input('code', '')) ?? '';
        if (strlen($code) !== 6) {
            return response()->json([
                'message' => 'Saisissez le code à 6 chiffres reçu par e-mail.',
            ], 422);
        }

        $otp = EncheresClientOtp::query()->where('email', $email)->first();
        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json([
                'message' => 'Ce code a expiré. Demandez-en un nouveau.',
            ], 422);
        }

        if ($otp->attempts >= 5) {
            $otp->delete();

            return response()->json([
                'message' => 'Trop de tentatives. Demandez un nouveau code.',
            ], 422);
        }

        if (! Hash::check($code, $otp->otp_hash)) {
            $otp->increment('attempts');

            return response()->json([
                'message' => 'Code incorrect.',
            ], 422);
        }

        $client = EncheresClient::query()->firstOrNew(['email' => $email]);
        $created = ! $client->exists;
        if ($created) {
            $local = strstr($email, '@', true) ?: 'Client';
            $client->name = Str::headline(str_replace(['.', '_', '-'], ' ', $local));
        }
        $plainToken = Str::random(64);
        $client->token_hash = hash('sha256', $plainToken);
        $client->email_verified_at = now();
        $client->save();
        $otp->delete();

        return response()->json([
            'token' => $plainToken,
            'created' => $created,
            'client' => [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $client = $this->clientFromToken($request);
        if (! $client) {
            return response()->json(['message' => 'Session expirée.'], 401);
        }

        return response()->json([
            'client' => [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $client = $this->clientFromToken($request);
        if ($client) {
            $client->token_hash = null;
            $client->save();
        }

        return response()->json(['message' => 'Déconnecté.']);
    }

    private function emailFrom(Request $request): JsonResponse|string
    {
        $raw = trim((string) $request->input('email', ''));
        if ($raw === '') {
            return response()->json([
                'message' => 'Saisissez votre adresse e-mail.',
            ], 422);
        }

        if ($this->looksLikePhone($raw) || ! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Saisissez une adresse e-mail. Le numéro de téléphone n’est pas accepté.',
            ], 422);
        }

        return Str::lower($raw);
    }

    private function looksLikePhone(string $value): bool
    {
        $compact = preg_replace('/[\s.\-()]+/', '', $value) ?? $value;

        return (bool) preg_match('/^\+?\d{8,15}$/', $compact);
    }

    private function clientFromToken(Request $request): ?EncheresClient
    {
        $token = trim((string) $request->header('X-Encheres-Client-Token', ''));
        if ($token === '') {
            return null;
        }

        return EncheresClient::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }
}
