<?php

use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EntityController;
use App\Http\Controllers\API\EnvironmentController;
use App\Http\Controllers\API\MethodologyPageController;
use App\Http\Controllers\API\OperationalRiskRowController;
use App\Http\Controllers\API\ReferentialController;
use App\Http\Controllers\API\ScaleLevelController;
use App\Http\Controllers\API\MissionController;
use App\Http\Controllers\API\MissionResponseController;
use App\Http\Controllers\API\MissionParametrageController;
use App\Http\Controllers\API\MissionTypeController;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post("auth/login", "login");

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix("/auth")->name("auth.")->group(function () {
            Route::get('data', "data")->name("data");
            Route::delete('logout', "logout")->name("logout");
        });

        // Route pour changer le mot de passe (accessible même si password_change_required est true)
        Route::put('users/update-password', [UserController::class, 'updatePassword'])->name('user.update-password');

        // Routes protégées par le middleware de statut de compte
        Route::middleware('account.status')->group(function () {
            Route::get('attachments/download', [AttachmentController::class, 'download'])->name('attachments.download');

            Route::prefix('methodology-pages')->name('methodology-page.')->controller(MethodologyPageController::class)->group(function () {
                Route::get('{slug}', 'showBySlug')->name('show');
                Route::put('{slug}', 'updateBySlug')->name('update');
            });

            Route::prefix('scale-levels')->name('scale-level.')->controller(ScaleLevelController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            Route::prefix('referentials')->name('referential.')->controller(ReferentialController::class)->group(function () {
                Route::get('countries', 'countries')->name('countries');
                Route::get('subsidiaries', 'subsidiaries')->name('subsidiaries');
                Route::get('departments', 'departments')->name('departments');
                Route::get('scales', 'scales')->name('scales');
                Route::get('echelle-pg', 'echellePg')->name('echelle-pg');
                Route::put('echelle-pg', 'updateEchellePg')->name('echelle-pg.update');
                Route::get('echelle-controle', 'echelleControle')->name('echelle-controle');
                Route::put('echelle-controle', 'updateEchelleControle')->name('echelle-controle.update');
                Route::get('matrix', 'matrix')->name('matrix');
                Route::get('matrice-risques', 'matriceRisques')->name('matrice-risques');
                Route::put('matrice-risques', 'updateMatriceRisques')->name('matrice-risques.update');
                Route::get('risk-families', 'riskFamilies')->name('risk-families');
                Route::get('lexique-familles', 'lexiqueFamilles')->name('lexique-familles');
                Route::put('lexique-familles', 'updateLexiqueFamilles')->name('lexique-familles.update');
                Route::get('top-risques', 'topRisques')->name('top-risques');
                Route::put('top-risques', 'updateTopRisques')->name('top-risques.update');
                Route::get('entities-departments', 'entitiesDepartments')->name('entities-departments');
                Route::get('analyse-risques/{code}', 'analyseRisques')->name('analyse-risques');
                Route::get('analyse-risques/{code}/historique', 'analyseRisquesHistorique')->name('analyse-risques.historique');
                Route::put('analyse-risques/{code}', 'updateAnalyseRisques')->name('analyse-risques.update');
            });

            Route::prefix('operational-risk-rows')->name('operational-risk-row.')->controller(OperationalRiskRowController::class)->group(function () {
                Route::post('departments/{code}', 'createForDepartment')->name('store');
                Route::put('{id}/phase1', 'updatePhase1')->name('phase1.update');
                Route::post('{id}/submit', 'submit')->name('submit');
                Route::post('{id}/request-revision', 'requestRevision')->name('request-revision');
                Route::post('{id}/validate-assign', 'validateAndAssign')->name('validate-assign');
                Route::put('{id}/phase2', 'updatePhase2')->name('phase2.update');
                Route::post('{id}/submit-entity', 'submitEntityPhase2')->name('submit-entity');
                Route::post('{id}/complete', 'completeEntityPhase2')->name('complete');
                Route::post('{id}/request-entity-revision', 'requestEntityRevision')->name('request-entity-revision');
                Route::delete('{id}', 'destroy')->name('destroy');
            });

            Route::prefix('environments')->name('environment.')->controller(EnvironmentController::class)->group(function () {
                Route::get('options', 'options')->name('options');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            Route::prefix('entities')->name('entity.')->controller(EntityController::class)->group(function () {
                Route::get('with-members', 'withMembers')->name('with-members');
                Route::get('by-environment/{environmentId}', 'byEnvironment')->name('by-environment');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            Route::prefix('mission-parametrage')->name('mission-parametrage.')->controller(MissionParametrageController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/', 'save')->name('save');
            });

            Route::prefix('mission-types')->name('mission-type.')->controller(MissionTypeController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/manage', 'manage')->name('manage');
                Route::post('/', 'store')->name('store');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            Route::prefix('missions')->name('mission.')->group(function () {
                Route::controller(MissionController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::get('/import/template', 'importTemplate')->name('import.template');
                    Route::post('/import', 'import')->name('import');
                    Route::get('/{id}', 'show')->name('show');
                    Route::post('/{id}/take-charge', 'takeCharge')->name('take-charge');
                    Route::post('/{id}/recommendation', 'storeRecommendation')->name('recommendation.store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });

                Route::controller(MissionResponseController::class)->group(function () {
                    Route::get('/{id}/agents', 'agents')->name('agents');
                    Route::post('/{id}/responses/action/start', 'startAction')->name('response.action.start');
                    Route::post('/{id}/responses/{responseId}/update', 'updateAction')->name('response.update');
                    Route::post('/{id}/responses/{responseId}/attachments', 'uploadAttachments')->name('response.attachments');
                    Route::post('/{id}/responses/{responseId}/submit-agent', 'submitAgent')->name('response.submit-agent');
                    Route::post('/{id}/responses/{responseId}/forward', 'forward')->name('response.forward');
                    Route::post('/{id}/responses/{responseId}/cancel', 'cancelAction')->name('response.cancel');
                    Route::post('/{id}/responses/{responseId}/validate', 'validateTransmission')->name('response.validate');
                    Route::post('/{id}/responses/passivite', 'submitPassivity')->name('response.passivite');
                });
            });

            Route::prefix('recommendations')->name('recommendation.')->controller(RecommendationController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/regulator-queue', 'regulatorQueue')->name('regulator-queue');
                Route::get('/action-plans/{planId}', 'showActionPlan')->name('action-plan.show');
                Route::put('/action-plans/{planId}', 'updateActionPlan')->name('action-plan.update');
                Route::delete('/action-plans/{planId}', 'destroyActionPlan')->name('action-plan.destroy');
                Route::post('/action-plans/{planId}/comments', 'appendActionPlanComments')->name('action-plans.comments');
                Route::post('/action-plans/{planId}/attachments', 'uploadActionPlanAttachments')->name('action-plans.attachments');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::post('/{id}/follow-ups', 'syncFollowUps')->name('follow-ups.sync');
                Route::post('/{id}/close', 'close')->name('close');
                Route::post('/{id}/transmit-regulator', 'transmitToRegulator')->name('transmit-regulator');
                Route::post('/{id}/regulator-comments', 'appendRegulatorComments')->name('regulator-comments');
                Route::post('/{id}/action-plans', 'syncActionPlans')->name('action-plans.sync');
                Route::post('/{id}/action-plan', 'storeActionPlan')->name('action-plan.store');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            // Routes utilisateurs
            Route::prefix('users')->name('user.')->controller(UserController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            // Routes supplémentaires sous autorisation
        });
    });
});
