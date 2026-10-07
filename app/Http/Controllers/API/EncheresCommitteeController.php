<?php

namespace App\Http\Controllers\API;

use App\Mail\EncheresCommitteeCodeMail;
use App\Mail\EncheresWinnerMail;
use App\Models\EncheresClient;
use App\Models\EncheresCommitteeAccess;
use App\Models\EncheresSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EncheresCommitteeController
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $this->isCommitteeUser($user)) {
            return response()->json(['message' => 'Accès réservé au comité.'], 403);
        }

        $seesAll = $this->isAuctionAdmin($user);

        $items = EncheresCommitteeAccess::query()
            ->latest()
            ->get()
            ->filter(fn (EncheresCommitteeAccess $row) => $seesAll || $this->userIsAssigned($row, $user))
            ->map(function (EncheresCommitteeAccess $row) use ($seesAll) {
                $bids = array_values($row->bids ?? []);

                return [
                    'auction_key' => $row->auction_key,
                    'lot' => $row->lot,
                    'title' => $row->title,
                    'bid_count' => count($bids),
                    'requires_code' => ! $seesAll,
                    'bids' => $seesAll ? $bids : null,
                ];
            })
            ->values();

        return response()->json(['data' => $items]);
    }

    public function syncLot(Request $request)
    {
        $user = $request->user();
        if (! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Seul un administrateur enchères peut enregistrer un bien.'], 403);
        }

        $data = $request->validate([
            'auction_key' => 'required|string|max:100',
            'lot' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'bids' => 'nullable|array',
        ]);

        $access = EncheresCommitteeAccess::query()->firstOrNew([
            'auction_key' => $data['auction_key'],
        ]);

        $access->lot = $data['lot'] ?? $access->lot;
        $access->title = $data['title'];

        if (! $access->exists) {
            $access->code_hash = Hash::make(bin2hex(random_bytes(16)));
            $access->committee = [];
            $access->bids = array_values($data['bids'] ?? []);
            $access->attempts = 0;
        }

        $access->save();

        return response()->json(['ok' => true]);
    }

    public function issue(Request $request)
    {
        $user = $request->user();
        if (! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Seul un administrateur enchères peut envoyer le code comité.'], 403);
        }

        $data = $request->validate([
            'auction_key' => 'required|string|max:100',
            'lot' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'committee_ids' => 'required|array|min:1',
            'committee_ids.*' => 'integer',
            'bids' => 'nullable|array',
        ]);

        $members = User::query()
            ->whereIn('id', $data['committee_ids'])
            ->get()
            ->filter(fn (User $member) => $this->isCommitteeUser($member))
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ])
            ->values();

        if ($members->isEmpty()) {
            return response()->json(['message' => 'Aucun membre du comité valide.'], 422);
        }

        $usedCodes = [];
        $committee = [];
        $codesByUserId = [];

        foreach ($members as $member) {
            do {
                $code = (string) random_int(100, 999);
            } while (isset($usedCodes[$code]));

            $usedCodes[$code] = true;
            $codesByUserId[$member['id']] = $code;
            $committee[] = [
                'id' => $member['id'],
                'name' => $member['name'],
                'email' => $member['email'],
                'code_hash' => Hash::make($code),
                'attempts' => 0,
            ];
        }

        $access = EncheresCommitteeAccess::query()->updateOrCreate(
            ['auction_key' => $data['auction_key']],
            [
                'lot' => $data['lot'] ?? null,
                'title' => $data['title'],
                'code_hash' => Hash::make(bin2hex(random_bytes(16))),
                'committee' => $committee,
                'bids' => array_values($data['bids'] ?? []),
                'attempts' => 0,
            ],
        );

        $sent = [];
        $failed = [];

        foreach ($committee as $member) {
            $recipient = User::query()->find($member['id']);
            $code = $codesByUserId[$member['id']] ?? null;
            if (! $recipient || ! $code) {
                continue;
            }

            try {
                Mail::to($recipient->email)->send(new EncheresCommitteeCodeMail(
                    $recipient,
                    $access->title,
                    $access->lot ?: $access->auction_key,
                    $code,
                ));
                $sent[] = $recipient->email;
            } catch (\Throwable $exception) {
                $failed[] = $recipient->email;
            }
        }

        return response()->json([
            'sent' => count($sent),
            'sent_to' => $sent,
            'failed' => $failed,
            'message' => count($failed)
                ? 'Codes personnels générés. Certains e-mails n’ont pas pu être envoyés.'
                : 'Un code personnel a été envoyé à chaque membre du comité.',
        ]);
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        if (! $this->isCommitteeUser($user)) {
            return response()->json(['message' => 'Accès réservé au comité.'], 403);
        }

        $data = $request->validate([
            'auction_key' => 'required|string|max:100',
            'code' => 'required|digits:3',
        ]);

        $access = EncheresCommitteeAccess::query()
            ->where('auction_key', $data['auction_key'])
            ->first();

        if (! $access) {
            return response()->json(['message' => 'Ce bien ne vous est pas assigné.'], 404);
        }

        $committee = $access->committee ?? [];
        $memberIndex = null;
        foreach ($committee as $index => $member) {
            if ((int) ($member['id'] ?? 0) === (int) $user->id) {
                $memberIndex = $index;
                break;
            }
        }

        if ($memberIndex === null) {
            return response()->json(['message' => 'Ce bien ne vous est pas assigné.'], 404);
        }

        $member = $committee[$memberIndex];
        $attempts = (int) ($member['attempts'] ?? 0);
        if ($attempts >= 8) {
            return response()->json(['message' => 'Trop de tentatives. Demandez un nouvel envoi de votre code.'], 429);
        }

        $hash = $member['code_hash'] ?? null;
        if (! is_string($hash) || ! Hash::check($data['code'], $hash)) {
            $committee[$memberIndex]['attempts'] = $attempts + 1;
            $access->committee = $committee;
            $access->save();

            return response()->json(['message' => 'Ce code ne correspond pas à votre accès.'], 422);
        }

        $committee[$memberIndex]['attempts'] = 0;
        $access->committee = $committee;
        $access->save();

        return response()->json([
            'auction_key' => $access->auction_key,
            'lot' => $access->lot,
            'title' => $access->title,
            'bids' => $access->bids ?? [],
        ]);
    }

    public function syncBid(Request $request)
    {
        $data = $request->validate([
            'auction_key' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'user_ref' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $access = EncheresCommitteeAccess::query()
            ->where('auction_key', $data['auction_key'])
            ->first();

        if (! $access) {
            return response()->json(['ok' => true]);
        }

        $bids = $access->bids ?? [];
        foreach ($bids as &$bid) {
            if (($bid['status'] ?? '') === 'Gagnante') {
                $bid['status'] = 'Dépassée';
            }
        }
        unset($bid);

        $bids[] = [
            'id' => (int) round(microtime(true) * 1000),
            'user_ref' => $data['user_ref'],
            'email' => isset($data['email']) ? strtolower($data['email']) : null,
            'amount' => (int) $data['amount'],
            'at' => now()->toIso8601String(),
            'status' => 'Gagnante',
        ];

        $access->bids = array_values($bids);
        $access->save();

        return response()->json(['ok' => true]);
    }

    public function winnerMessage(): JsonResponse
    {
        $user = request()->user();
        if (! $user || ! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Accès réservé à l’administration.'], 403);
        }

        return response()->json($this->winnerTemplate());
    }

    public function updateWinnerMessage(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Accès réservé à l’administration.'], 403);
        }

        $data = $request->validate([
            'subject' => 'required|string|max:180',
            'body' => 'required|string|max:5000',
        ]);

        EncheresSetting::putValue('winner_subject', trim($data['subject']));
        EncheresSetting::putValue('winner_body', trim($data['body']));

        return response()->json($this->winnerTemplate());
    }

    public function notifyWinner(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->isCommitteeUser($user)) {
            return response()->json(['message' => 'Accès réservé au comité.'], 403);
        }

        $data = $request->validate([
            'auction_key' => 'required|string|max:100',
            'bid_id' => 'required',
        ]);

        $access = EncheresCommitteeAccess::query()
            ->where('auction_key', $data['auction_key'])
            ->first();

        if (! $access) {
            return response()->json(['message' => 'Bien introuvable.'], 404);
        }

        if (! $this->isAuctionAdmin($user) && ! $this->userIsAssigned($access, $user)) {
            return response()->json(['message' => 'Ce bien ne vous est pas assigné.'], 403);
        }

        $bid = collect($access->bids ?? [])->first(function ($row) use ($data) {
            return (string) ($row['id'] ?? '') === (string) $data['bid_id'];
        });

        if (! is_array($bid) || ! str_contains(mb_strtolower((string) ($bid['status'] ?? '')), 'gagn')) {
            return response()->json(['message' => 'Seul le gagnant peut recevoir ce message.'], 422);
        }

        $email = $this->winnerEmail($bid);
        if (! $email) {
            return response()->json([
                'message' => 'Aucune adresse e-mail trouvée pour '.$bid['user_ref'].'.',
            ], 422);
        }

        $template = $this->winnerTemplate();
        $replacements = [
            '{nom}' => (string) ($bid['user_ref'] ?? ''),
            '{email}' => $email,
            '{lot}' => (string) ($access->lot ?: $access->auction_key),
            '{titre}' => (string) $access->title,
            '{montant}' => number_format((float) ($bid['amount'] ?? 0), 0, ',', ' ').' FCFA',
        ];
        $subject = strtr($template['subject'], $replacements);
        $body = strtr($template['body'], $replacements);

        try {
            Mail::to($email)->send(new EncheresWinnerMail($subject, $body));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'L’e-mail n’a pas pu être envoyé. Réessayez.',
            ], 500);
        }

        return response()->json([
            'message' => 'Message envoyé à '.$email.'.',
            'email' => $email,
        ]);
    }

    private function winnerTemplate(): array
    {
        return [
            'subject' => EncheresSetting::getValue(
                'winner_subject',
                'Félicitations, votre offre est gagnante — {lot}',
            ),
            'body' => EncheresSetting::getValue(
                'winner_body',
                "Bonjour {nom},\n\nVotre offre est retenue pour le bien « {titre} » (référence {lot}).\n\nL’équipe EnchèreSN vous contactera pour les prochaines étapes.\n\nCordialement,\nEnchèreSN",
            ),
        ];
    }

    private function winnerEmail(array $bid): ?string
    {
        $direct = strtolower(trim((string) ($bid['email'] ?? '')));
        if (filter_var($direct, FILTER_VALIDATE_EMAIL)) {
            return $direct;
        }

        $ref = trim((string) ($bid['user_ref'] ?? ''));
        if (filter_var($ref, FILTER_VALIDATE_EMAIL)) {
            return strtolower($ref);
        }

        if ($ref === '') {
            return null;
        }

        $needle = mb_strtolower($ref);
        $client = EncheresClient::query()
            ->whereRaw('lower(email) = ?', [$needle])
            ->orWhereRaw('lower(name) = ?', [$needle])
            ->first();
        if ($client?->email) {
            return strtolower($client->email);
        }

        $account = User::query()
            ->whereRaw('lower(email) = ?', [$needle])
            ->orWhereRaw('lower(name) = ?', [$needle])
            ->first();

        return $account?->email ? strtolower($account->email) : null;
    }

    private function isCommitteeUser(User $user): bool
    {
        if (in_array($user->profile, ['super_admin', 'admin', 'comite', 'admin_encheres'], true)) {
            return true;
        }

        $role = $user->module_profiles['vente-encheres']['profile'] ?? null;

        return in_array($role, ['comite', 'admin_encheres'], true);
    }

    private function isAuctionAdmin(User $user): bool
    {
        if (in_array($user->profile, ['super_admin', 'admin', 'admin_encheres'], true)) {
            return true;
        }

        return ($user->module_profiles['vente-encheres']['profile'] ?? null) === 'admin_encheres';
    }

    private function userIsAssigned(EncheresCommitteeAccess $access, User $user): bool
    {
        foreach ($access->committee ?? [] as $member) {
            if ((int) ($member['id'] ?? 0) === (int) $user->id) {
                return true;
            }
        }

        return false;
    }
};
