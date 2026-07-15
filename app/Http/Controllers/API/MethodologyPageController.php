<?php

namespace App\Http\Controllers\API;

use App\Models\MethodologyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Méthodologie
 */
class MethodologyPageController extends APIController
{
    public function showBySlug(string $slug)
    {
        $page = MethodologyPage::query()->where('slug', $slug)->first();

        if (! $page) {
            return $this->responseError(['slug' => ['Page introuvable']], 404);
        }

        if (! Gate::inspect('view', $page)->allowed()) {
            return $this->responseError(['auth' => ['Page inactive']], 403);
        }

        return $this->responseOk(['methodology_page' => $page]);
    }

    public function updateBySlug(Request $request, string $slug)
    {
        $page = MethodologyPage::query()->where('slug', $slug)->first();

        if (! $page) {
            return $this->responseError(['slug' => ['Page introuvable']], 404);
        }

        if (! Gate::inspect('update', $page)->allowed()) {
            return $this->responseError(['auth' => ['Action non autorisée']], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'layout' => 'sometimes|in:classic,preambule,grid',
            'grid_data' => 'sometimes|array',
            'grid_data.columns' => 'required_with:grid_data|array|min:1',
            'grid_data.columns.*' => 'string|max:255',
            'grid_data.rows' => 'required_with:grid_data|array|min:1',
            'grid_data.rows.*.label' => 'required|string|max:255',
            'grid_data.rows.*.statement' => 'required|string',
            'grid_data.rows.*.explanation' => 'required|string',
            'introduction' => 'nullable|string',
            'sections' => 'sometimes|array',
            'sections.*.title' => 'required_with:sections|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.subtitle' => 'nullable|string|max:255',
            'sections.*.items' => 'nullable|array',
            'sections.*.items.*' => 'string',
            'conclusion' => 'nullable|string',
            'body_html' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $page->update($validator->validated());

        return $this->responseOk(['methodology_page' => $page->fresh()]);
    }
}
