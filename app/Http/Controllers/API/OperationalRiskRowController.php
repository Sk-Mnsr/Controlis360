<?php

namespace App\Http\Controllers\API;

use App\Enums\OperationalRiskRowStatus;
use App\Models\Entity;
use App\Models\OperationalRiskRow;
use App\Models\RiskClassification;
use App\Models\User;
use App\Support\OperationalRiskLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Analyse des risques opérationnels
 */
class OperationalRiskRowController extends APIController
{
    public function createForDepartment(Request $request, string $code)
    {
        $department = $this->resolveDepartmentEntity($request, $code);

        if (! $department) {
            return $this->responseError(['code' => ['Département introuvable']], 404);
        }

        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->canCreateOperationalRiskRow()) {
            return $this->responseError(['auth' => ['Action réservée au personnel du contrôle interne']], 403);
        }

        $validator = Validator::make($request->all(), $this->phase1Rules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $sortOrder = OperationalRiskRow::query()
            ->where('entity_id', $department->id)
            ->max('sort_order') + 1;

        $row = OperationalRiskRow::query()->create(array_merge(
            $this->phase1Payload($validator->validated()),
            [
                'entity_id' => $department->id,
                'status' => OperationalRiskRowStatus::Draft,
                'created_by_id' => $user->id,
                'sort_order' => $sortOrder ?: 1,
            ]
        ));

        OperationalRiskLogger::log($row, $user, 'created');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function updatePhase1(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $row->canEditPhase1By($request->user())) {
            return $this->responseError(['auth' => ['Modification non autorisée pour cette ligne']], 403);
        }

        $validator = Validator::make($request->all(), $this->phase1Rules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $row->update($this->phase1Payload($validator->validated()));

        OperationalRiskLogger::log($row, $request->user(), 'updated');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function submit(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->canCreateOperationalRiskRow()) {
            return $this->responseError(['auth' => ['Action réservée au personnel du contrôle interne']], 403);
        }

        if (! in_array($row->status, [OperationalRiskRowStatus::Draft, OperationalRiskRowStatus::RevisionRequested], true)) {
            return $this->responseError(['status' => ['Cette ligne ne peut pas être soumise']], 422);
        }

        $row->update([
            'status' => OperationalRiskRowStatus::Submitted,
            'revision_comment' => null,
            'submitted_at' => now(),
            'assigned_entity_id' => $row->entity_id,
        ]);

        OperationalRiskLogger::log($row, $user, 'submitted');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function requestRevision(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->canValidate($request->user())) {
            return $this->responseError(['auth' => ['Action réservée au responsable contrôle']], 403);
        }

        if ($row->status !== OperationalRiskRowStatus::Submitted) {
            return $this->responseError(['status' => ['Seules les lignes soumises peuvent être renvoyées']], 422);
        }

        $validator = Validator::make($request->all(), [
            'revision_comment' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $comment = $request->input('revision_comment');

        $row->update([
            'status' => OperationalRiskRowStatus::RevisionRequested,
            'revision_comment' => $comment,
        ]);

        OperationalRiskLogger::log($row, $request->user(), 'revision_requested', $comment);

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function validateAndAssign(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->canValidate($request->user())) {
            return $this->responseError(['auth' => ['Action réservée au responsable contrôle']], 403);
        }

        if ($row->status !== OperationalRiskRowStatus::Submitted) {
            return $this->responseError(['status' => ['Seules les lignes soumises peuvent être validées']], 422);
        }

        $validator = Validator::make($request->all(), [
            'deadline' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $assignedEntityId = $row->assigned_entity_id ?? $row->entity_id;

        if (! $assignedEntityId) {
            return $this->responseError(['entity' => ['Aucune entité affectée sur cette ligne']], 422);
        }

        $assignedEntity = Entity::query()
            ->where('id', $assignedEntityId)
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->first();

        if (! $assignedEntity) {
            return $this->responseError(['entity' => ['Entité affectée invalide']], 422);
        }

        $row->update([
            'status' => OperationalRiskRowStatus::Assigned,
            'revision_comment' => null,
            'assigned_entity_id' => $assignedEntity->id,
            'deadline' => $request->input('deadline'),
            'validated_by_id' => $request->user()->id,
            'validated_at' => now(),
        ]);

        OperationalRiskLogger::log(
            $row,
            $request->user(),
            'validated',
            "Affecté à {$assignedEntity->name}",
            ['assigned_entity_id' => $assignedEntity->id, 'deadline' => $request->input('deadline')]
        );

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function updatePhase2(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $row->canEditPhase2By($request->user())) {
            return $this->responseError(['auth' => ['Complétion réservée au responsable de l\'entité affectée']], 403);
        }

        $validator = Validator::make($request->all(), $this->phase2Rules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $row->update(array_merge(
            $this->phase2Payload($validator->validated()),
            [
                'residual_gravity' => $row->gravity,
                'residual_probability' => OperationalRiskRow::computeResidualProbability(
                    $row->probability,
                    $validator->validated()['control_effectiveness'] ?? null
                ),
            ]
        ));

        OperationalRiskLogger::log($row, $request->user(), 'updated');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function submitEntityPhase2(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $row->canEditPhase2By($request->user())) {
            return $this->responseError(['auth' => ['Envoi réservé au responsable de l\'entité affectée']], 403);
        }

        $validator = Validator::make($request->all(), $this->phase2Rules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $row->update(array_merge(
            $this->phase2Payload($validator->validated()),
            [
                'residual_gravity' => $row->gravity,
                'residual_probability' => OperationalRiskRow::computeResidualProbability(
                    $row->probability,
                    $validator->validated()['control_effectiveness'] ?? null
                ),
                'status' => OperationalRiskRowStatus::EntitySubmitted,
                'revision_comment' => null,
            ]
        ));

        OperationalRiskLogger::log($row, $request->user(), 'entity_submitted');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function completeEntityPhase2(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->canValidate($request->user())) {
            return $this->responseError(['auth' => ['Action réservée au responsable contrôle']], 403);
        }

        if ($row->status !== OperationalRiskRowStatus::EntitySubmitted) {
            return $this->responseError(['status' => ['Seules les lignes soumises par l\'entité peuvent être validées']], 422);
        }

        $row->update([
            'status' => OperationalRiskRowStatus::Completed,
            'revision_comment' => null,
        ]);

        OperationalRiskLogger::log($row, $request->user(), 'completed');

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function requestEntityRevision(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->canValidate($request->user())) {
            return $this->responseError(['auth' => ['Action réservée au responsable contrôle']], 403);
        }

        if ($row->status !== OperationalRiskRowStatus::EntitySubmitted) {
            return $this->responseError(['status' => ['Seules les lignes soumises par l\'entité peuvent être renvoyées']], 422);
        }

        $validator = Validator::make($request->all(), [
            'revision_comment' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $comment = $request->input('revision_comment');

        $row->update([
            'status' => OperationalRiskRowStatus::Assigned,
            'revision_comment' => $comment,
        ]);

        OperationalRiskLogger::log($row, $request->user(), 'entity_revision_requested', $comment);

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function destroy(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        $user = $request->user();

        if (! $user->isSuperAdmin() && ! ($user->canCreateOperationalRiskRow() && $row->status === OperationalRiskRowStatus::Draft)) {
            return $this->responseError(['auth' => ['Suppression non autorisée']], 403);
        }

        OperationalRiskLogger::log($row, $user, 'deleted', $row->major_exceptions, [
            'sub_process_name' => $row->sub_process_name,
            'row_id' => $row->id,
        ]);

        $row->delete();

        return $this->responseOk(['deleted' => true]);
    }

    private function canValidate(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isControleResponsable();
    }

    private function findRow(int $id): ?OperationalRiskRow
    {
        $row = OperationalRiskRow::query()
            ->with(['assignedEntity', 'entity'])
            ->find($id);

        if (! $row) {
            return null;
        }

        $user = request()->user();

        if ($user && ! $row->isVisibleTo($user)) {
            return null;
        }

        return $row;
    }

    private function resolveDepartmentEntity(Request $request, string $code): ?Entity
    {
        return Entity::resolveDepartmentForUser(
            $request->user(),
            $code,
            $request->query('environment'),
            $request->integer('entity_id') ?: null
        );
    }

    private function phase1Rules(): array
    {
        return [
            'process_number' => 'nullable|integer|min:1|max:99',
            'process_name' => 'nullable|string|max:255',
            'ratio' => 'nullable|numeric|min:0|max:100',
            'sub_process_name' => 'required|string|max:255',
            'line_date' => 'nullable|date',
            'major_exceptions' => 'required|string',
            'correlated_risks' => 'nullable|string',
            'risk_family' => 'nullable|string|max:255',
            'gravity' => 'nullable|integer|min:1|max:6',
            'probability' => 'nullable|integer|min:1|max:6',
        ];
    }

    private function phase1Payload(array $data): array
    {
        return [
            'process_number' => $data['process_number'] ?? null,
            'process_name' => $data['process_name'] ?? null,
            'ratio' => $data['ratio'] ?? null,
            'sub_process_name' => $data['sub_process_name'],
            'line_date' => $data['line_date'] ?? null,
            'major_exceptions' => $data['major_exceptions'] ?? null,
            'correlated_risks' => $data['correlated_risks'] ?? null,
            'risk_family' => $data['risk_family'] ?? null,
            'gravity' => $data['gravity'] ?? null,
            'probability' => $data['probability'] ?? null,
        ];
    }

    private function phase2Rules(): array
    {
        return [
            'control_description' => 'nullable|string',
            'control_exists' => 'nullable|boolean',
            'control_owner' => 'nullable|string|max:255',
            'control_effectiveness' => 'nullable|integer|min:1|max:5',
            'residual_gravity' => 'nullable|integer|min:1|max:6',
            'residual_probability' => 'nullable|numeric|min:1|max:6',
        ];
    }

    private function phase2Payload(array $data): array
    {
        return [
            'control_description' => $data['control_description'] ?? null,
            'control_exists' => $data['control_exists'] ?? null,
            'control_owner' => $data['control_owner'] ?? null,
            'control_effectiveness' => $data['control_effectiveness'] ?? null,
        ];
    }

    private function formatRowResiduals(OperationalRiskRow $row): array
    {
        $residualGravity = $row->resolvedResidualGravity();
        $residualProbability = $row->resolvedResidualProbability();
        $residualRisk = $row->residual_risk;
        $residualClassification = ($residualGravity && $residualProbability)
            ? RiskClassification::forCell($residualGravity, (int) round($residualProbability))
            : null;

        return [
            'residual_gravity' => $residualGravity,
            'residual_probability' => $residualProbability,
            'residual_risk' => $residualRisk,
            'residual_classification' => $residualClassification,
        ];
    }

    public function formatRow(OperationalRiskRow $row): array
    {
        $grossClassification = ($row->gravity && $row->probability)
            ? RiskClassification::forCell($row->gravity, $row->probability)
            : null;
        $residuals = $this->formatRowResiduals($row);

        $status = $row->status instanceof OperationalRiskRowStatus
            ? $row->status
            : OperationalRiskRowStatus::tryFrom((string) $row->status);

        return [
            'id' => $row->id,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'revision_comment' => $row->revision_comment,
            'assigned_entity_id' => $row->assigned_entity_id,
            'assigned_entity' => $row->assignedEntity,
            'entity_id' => $row->entity_id,
            'entity' => $row->entity,
            'deadline' => $row->deadline?->format('Y-m-d'),
            'process_number' => $row->process_number,
            'process_name' => $row->process_name,
            'ratio' => $row->ratio,
            'sub_process_name' => $row->sub_process_name,
            'line_date' => $row->line_date?->format('Y-m-d'),
            'major_exceptions' => $row->major_exceptions,
            'correlated_risks' => $row->correlated_risks,
            'risk_family' => $row->risk_family,
            'gravity' => $row->gravity,
            'probability' => $row->probability,
            'gross_risk' => $row->gross_risk,
            'gross_classification' => $grossClassification,
            'control_description' => $row->control_description,
            'control_exists' => $row->control_exists,
            'control_owner' => $row->control_owner,
            'control_effectiveness' => $row->control_effectiveness,
            'residual_gravity' => $residuals['residual_gravity'],
            'residual_probability' => $residuals['residual_probability'],
            'residual_risk' => $residuals['residual_risk'],
            'residual_classification' => $residuals['residual_classification'],
            'sort_order' => $row->sort_order,
        ];
    }
}
