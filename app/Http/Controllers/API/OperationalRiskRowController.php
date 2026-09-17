<?php

namespace App\Http\Controllers\API;

use App\Enums\OperationalRiskRowStatus;
use App\Mail\OperationalRiskAssignedMail;
use App\Mail\OperationalRiskCompletedMail;
use App\Mail\OperationalRiskEntityRevisionMail;
use App\Mail\OperationalRiskEntitySubmittedMail;
use App\Mail\OperationalRiskRevisionRequestedMail;
use App\Mail\OperationalRiskSubmittedMail;
use App\Models\Entity;
use App\Models\OperationalRiskRow;
use App\Models\RiskClassification;
use App\Models\User;
use App\Support\OperationalRiskLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $payload = $this->phase1Payload($validator->validated());

        if (($payload['process_number'] ?? null) === null) {
            $payload['process_number'] = ((int) OperationalRiskRow::query()
                ->where('entity_id', $department->id)
                ->max('process_number')) + 1;
        }

        $sortOrder = OperationalRiskRow::query()
            ->where('entity_id', $department->id)
            ->max('sort_order') + 1;

        $row = OperationalRiskRow::query()->create(array_merge(
            $payload,
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

    public function updateSubProcess(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $row->canEditPhase1By($request->user())) {
            return $this->responseError(['auth' => ['Modification non autorisée pour cette ligne']], 403);
        }

        $validator = Validator::make($request->all(), [
            'process_number' => 'required|integer|min:1|max:99',
            'process_name' => 'required|string|max:255',
            'ratio' => 'required|numeric|min:0|max:100',
            'sub_process_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $row->update($validator->validated());

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

        $missing = [];
        if ($row->process_number === null) {
            $missing['process_number'] = ['Le N° est obligatoire avant envoi.'];
        }
        if (! trim((string) $row->process_name)) {
            $missing['process_name'] = ['Le processus est obligatoire avant envoi.'];
        }
        if ($row->ratio === null) {
            $missing['ratio'] = ['Le ratio est obligatoire avant envoi.'];
        }
        if (! trim((string) $row->sub_process_name)) {
            $missing['sub_process_name'] = ['Le sous-processus est obligatoire avant envoi.'];
        }
        if (! $row->line_date) {
            $missing['line_date'] = ['La date ligne est obligatoire avant envoi.'];
        }
        if (! trim((string) $row->major_exceptions)) {
            $missing['major_exceptions'] = ['Les risques identifiés sont obligatoires avant envoi.'];
        }
        if (! trim((string) $row->correlated_risks)) {
            $missing['correlated_risks'] = ['Les risques corrélés sont obligatoires avant envoi.'];
        }
        if (! trim((string) $row->risk_family)) {
            $missing['risk_family'] = ['La famille de risque est obligatoire avant envoi.'];
        }
        if ($row->gravity === null) {
            $missing['gravity'] = ['La gravité (G) est obligatoire avant envoi.'];
        }
        if ($row->probability === null) {
            $missing['probability'] = ['La probabilité (P) est obligatoire avant envoi.'];
        }

        if ($missing !== []) {
            return $this->responseError($missing, 422);
        }

        $row->update([
            'status' => OperationalRiskRowStatus::Submitted,
            'revision_comment' => null,
            'submitted_at' => now(),
            'assigned_entity_id' => $row->entity_id,
        ]);

        OperationalRiskLogger::log($row, $user, 'submitted');

        $this->notifyControleResponsablesSubmitted($row->fresh(['entity', 'createdBy']), $user);

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

        $this->notifyCreatorRevisionRequested($row->fresh(['entity', 'createdBy']), $request->user(), $comment);

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

        $this->notifyEntityResponsablesAssigned(
            $row->fresh(['entity', 'assignedEntity']),
            $request->user()
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

        $this->notifyControleResponsablesEntitySubmitted(
            $row->fresh(['entity', 'assignedEntity']),
            $request->user()
        );

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

        $this->notifyEntityResponsablesCompleted(
            $row->fresh(['entity', 'assignedEntity', 'createdBy']),
            $request->user()
        );

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

        $this->notifyEntityResponsablesRevision(
            $row->fresh(['entity', 'assignedEntity']),
            $request->user(),
            $comment
        );

        return $this->responseOk(['row' => $this->formatRow($row->fresh(['assignedEntity']))]);
    }

    public function destroy(Request $request, int $id)
    {
        $row = $this->findRow($id);

        if (! $row) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        $user = $request->user();

        if (! $user->isPlatformAdministrator() && ! ($user->canCreateOperationalRiskRow() && $row->status === OperationalRiskRowStatus::Draft)) {
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
        return $user->isPlatformAdministrator() || $user->isControleResponsable();
    }

    private function notifyControleResponsablesSubmitted(OperationalRiskRow $row, User $sender): void
    {
        $recipients = $this->controleResponsables()
            ->reject(fn (User $user) => $user->id === $sender->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new OperationalRiskSubmittedMail($row, $recipient, $sender)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail risque soumis échoué', [
                    'row_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function notifyCreatorRevisionRequested(OperationalRiskRow $row, User $requester, string $comment): void
    {
        $creator = $row->createdBy;
        if (! $creator || blank($creator->email) || $creator->id === $requester->id) {
            return;
        }

        try {
            Mail::to($creator->email)->send(
                new OperationalRiskRevisionRequestedMail($row, $creator, $requester, $comment)
            );
        } catch (\Throwable $e) {
            Log::warning('Envoi mail révision risque échoué', [
                'row_id' => $row->id,
                'recipient_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyEntityResponsablesAssigned(OperationalRiskRow $row, User $validator): void
    {
        $entity = $row->assignedEntity;
        if (! $entity) {
            return;
        }

        $recipients = $entity->responsables()
            ->whereNotNull('email')
            ->get()
            ->reject(fn (User $user) => $user->id === $validator->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new OperationalRiskAssignedMail($row, $recipient, $validator)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail risque affecté échoué', [
                    'row_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function notifyControleResponsablesEntitySubmitted(OperationalRiskRow $row, User $sender): void
    {
        $recipients = $this->controleResponsables()
            ->reject(fn (User $user) => $user->id === $sender->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new OperationalRiskEntitySubmittedMail($row, $recipient, $sender)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail complétion entité risque échoué', [
                    'row_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function notifyEntityResponsablesRevision(OperationalRiskRow $row, User $requester, string $comment): void
    {
        $entity = $row->assignedEntity;
        if (! $entity) {
            return;
        }

        $recipients = $entity->responsables()
            ->whereNotNull('email')
            ->get()
            ->reject(fn (User $user) => $user->id === $requester->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new OperationalRiskEntityRevisionMail($row, $recipient, $requester, $comment)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail révision entité risque échoué', [
                    'row_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function notifyEntityResponsablesCompleted(OperationalRiskRow $row, User $validator): void
    {
        $recipients = collect();

        if ($row->assignedEntity) {
            $recipients = $recipients->merge(
                $row->assignedEntity->responsables()->whereNotNull('email')->get()
            );
        }

        if ($row->createdBy && filled($row->createdBy->email)) {
            $recipients->push($row->createdBy);
        }

        $recipients = $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $validator->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new OperationalRiskCompletedMail($row, $recipient, $validator)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail risque clôturé échoué', [
                    'row_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function controleResponsables(): Collection
    {
        return User::query()
            ->where('activated', true)
            ->whereNotNull('email')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('profile', 'controle')
                        ->where('controle_role', 'responsable_controle_permanent');
                })->orWhere(function ($inner) {
                    $inner->where('module_profiles->cartographie->profile', 'controle')
                        ->where('module_profiles->cartographie->controle_role', 'responsable_controle_permanent');
                });
            })
            ->get();
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
            'process_number' => 'required|integer|min:1|max:99',
            'process_name' => 'required|string|max:255',
            'ratio' => 'required|numeric|min:0|max:100',
            'sub_process_name' => 'required|string|max:255',
            'line_date' => 'required|date',
            'major_exceptions' => 'required|string|min:3',
            'correlated_risks' => 'required|string|max:255',
            'risk_family' => 'required|string|max:255',
            'gravity' => 'required|integer|min:1|max:6',
            'probability' => 'required|integer|min:1|max:6',
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
            'control_description' => 'required|string|min:3',
            'control_exists' => 'required|boolean',
            'control_owner' => 'required|string|max:255',
            'control_effectiveness' => 'required|integer|min:1|max:5',
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
