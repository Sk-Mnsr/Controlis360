<?php

namespace App\Http\Controllers\API;

use App\Models\EncheresAuction;
use App\Models\User;
use Illuminate\Http\Request;

class EncheresAuctionController
{
    public function index(Request $request)
    {
        $items = $this->publishedPayloads();

        $q = mb_strtolower(trim((string) $request->query('q', $request->query('search', ''))));
        if ($q !== '') {
            $items = array_values(array_filter($items, function (array $item) use ($q) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $item['title'] ?? '',
                    $item['lot'] ?? '',
                    $item['location'] ?? '',
                    $item['brand'] ?? '',
                    $item['model'] ?? '',
                    $item['category_label'] ?? '',
                ])));

                return str_contains($haystack, $q);
            }));
        }

        if ($request->filled('category_id')) {
            $categoryId = (string) $request->query('category_id');
            $items = array_values(array_filter($items, fn (array $item) => (string) ($item['category_id'] ?? '') === $categoryId));
        }

        if ($request->filled('region')) {
            $region = (string) $request->query('region');
            $items = array_values(array_filter($items, fn (array $item) => (string) ($item['region'] ?? '') === $region));
        }

        if ($request->filled('min_price')) {
            $min = (float) $request->query('min_price');
            $items = array_values(array_filter($items, fn (array $item) => (float) ($item['current_price'] ?? 0) >= $min));
        }

        if ($request->filled('max_price')) {
            $max = (float) $request->query('max_price');
            $items = array_values(array_filter($items, fn (array $item) => (float) ($item['current_price'] ?? 0) <= $max));
        }

        return response()->json(['data' => array_values($items)]);
    }

    public function show(string $key)
    {
        $row = EncheresAuction::query()->where('auction_key', $key)->first();
        if (! $row || ! $row->published) {
            return response()->json(['message' => 'Enchère introuvable.'], 404);
        }

        return response()->json(['data' => $row->payload]);
    }

    public function sync(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Seul un administrateur enchères peut publier le catalogue.'], 403);
        }

        $auctions = $request->input('auctions');
        if (! is_array($auctions)) {
            return response()->json(['message' => 'Catalogue invalide.'], 422);
        }

        foreach ($auctions as $item) {
            if (! is_array($item) || ! isset($item['id'])) {
                continue;
            }

            EncheresAuction::query()->updateOrCreate(
                ['auction_key' => (string) $item['id']],
                [
                    'published' => $this->isPublished($item),
                    'payload' => $item,
                ],
            );
        }

        return response()->json(['ok' => true]);
    }

    public function deposits(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->isCommitteeUser($user)) {
            return response()->json(['message' => 'Accès réservé au comité.'], 403);
        }

        $seesAll = $this->isAuctionAdmin($user);

        $items = EncheresAuction::query()
            ->latest()
            ->get()
            ->map(fn (EncheresAuction $row) => is_array($row->payload) ? $row->payload : [])
            ->filter(fn (array $item) => isset($item['id']))
            ->filter(fn (array $item) => $seesAll || (int) ($item['deposited_by'] ?? 0) === (int) $user->id)
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

    public function deposit(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->isCommitteeUser($user)) {
            return response()->json(['message' => 'Seuls le comité et l’administrateur enchères peuvent déposer un bien.'], 403);
        }

        $item = $request->input('auction');
        if (! is_array($item) || empty($item['id'])) {
            return response()->json(['message' => 'Bien invalide.'], 422);
        }

        $key = (string) $item['id'];
        $isAdmin = $this->isAuctionAdmin($user);
        $existing = EncheresAuction::query()->where('auction_key', $key)->first();
        $existingPayload = is_array($existing?->payload) ? $existing->payload : [];
        $depositedBy = (int) ($existingPayload['deposited_by'] ?? 0);

        if ($existing && ! $isAdmin && $depositedBy !== (int) $user->id) {
            return response()->json(['message' => 'Vous ne pouvez modifier que les biens que vous avez déposés.'], 403);
        }

        if (! $isAdmin) {
            $item['admin_status'] = 'draft';
            $item['published'] = false;
            $item['status'] = 'pending';
        }

        $item['deposited_by'] = $existing && $depositedBy ? $depositedBy : $user->id;
        $item['deposited_by_name'] = $existingPayload['deposited_by_name'] ?? $user->name;

        EncheresAuction::query()->updateOrCreate(
            ['auction_key' => $key],
            [
                'published' => $this->isPublished($item),
                'payload' => $item,
            ],
        );

        return response()->json(['ok' => true, 'data' => $item]);
    }

    public function destroy(Request $request, string $key)
    {
        $user = $request->user();
        if (! $user || ! $this->isAuctionAdmin($user)) {
            return response()->json(['message' => 'Seul un administrateur enchères peut supprimer un bien.'], 403);
        }

        EncheresAuction::query()->where('auction_key', $key)->delete();

        return response()->json(['ok' => true]);
    }

    private function publishedPayloads(): array
    {
        return EncheresAuction::query()
            ->where('published', true)
            ->latest()
            ->get()
            ->map(fn (EncheresAuction $row) => is_array($row->payload) ? $row->payload : [])
            ->filter(fn (array $item) => isset($item['id']))
            ->values()
            ->all();
    }

    private function isPublished(array $item): bool
    {
        return ($item['published'] ?? true) !== false
            && ($item['admin_status'] ?? 'published') !== 'draft';
    }

    private function isAuctionAdmin(User $user): bool
    {
        if (in_array($user->profile, ['super_admin', 'admin', 'admin_encheres'], true)) {
            return true;
        }

        return ($user->module_profiles['vente-encheres']['profile'] ?? null) === 'admin_encheres';
    }

    private function isCommitteeUser(User $user): bool
    {
        if (in_array($user->profile, ['super_admin', 'admin', 'comite', 'admin_encheres'], true)) {
            return true;
        }

        return in_array($user->module_profiles['vente-encheres']['profile'] ?? null, ['comite', 'admin_encheres'], true);
    }
}
