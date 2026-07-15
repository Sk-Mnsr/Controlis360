<?php

namespace App\Http\Controllers\API;

use App\Services\AttachmentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maravel\Http\Controllers\APIController;

class AttachmentController extends APIController
{
    public function __construct(
        private AttachmentAccessService $attachmentAccessService,
    ) {}

    public function download(Request $request)
    {
        $path = (string) $request->query('path', '');

        if ($path === '' || str_contains($path, '..')) {
            return $this->responseError(['message' => ['Fichier introuvable.']], 404);
        }

        if (! Storage::disk('local')->exists($path)) {
            return $this->responseError(['message' => ['Fichier introuvable.']], 404);
        }

        $user = $request->user();

        if (! $this->attachmentAccessService->canDownload($user, $path)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        return Storage::disk('local')->download($path, basename($path));
    }
}
