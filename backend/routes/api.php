<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityTypesController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\TimelineCommentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentPartyController;
use App\Http\Controllers\IncidentPCOController;
use App\Http\Controllers\IncidentPriorityController;
use App\Http\Controllers\IncidentStateController;
use App\Http\Controllers\IncidentTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use App\Http\Resources\TimelineCommentResource;
use App\Http\Resources\UserResource;
use App\Models\Entity;
use App\Models\Incident;
use App\Models\TimelineComment;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use App\Models\IncidentPriority;
use App\Models\IncidentState;
use App\Models\IncidentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

Route::post('/test/reset', function (Request $request) {
    if ($request->header('TEST-TOKEN') !== 'civisafe-test') {
        abort(403);
    }

    \Log::info('RESET START');

    Artisan::call('migrate:fresh');
    \Log::info('MIGRATE DONE');

    Artisan::call('db:seed');
    \Log::info('SEED DONE');

    return response()->noContent();
});

Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);

    Route::get('up', function () {
       return response()->json(['status' => 'up']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('logout', [AuthController::class, 'logout']);

        //Custom para seguir a regra de versionamento da API
        Route::post('broadcasting/auth', function (Request $request) {
            return Broadcast::auth($request);
        });

        Route::apiResource('/users', UserController::class);
        Route::get('/user', function (Request $request) {
            return new UserResource($request->user()->load('roles'));
        });

        Route::prefix('notifications')->group(function () {
           Route::get('/', [NotificationController::class, 'index']);
           Route::delete('/', [NotificationController::class, 'readAll']);
           Route::delete('/{notification}', [NotificationController::class, 'read']);
        });

        Route::apiResource('/volunteers', VolunteerController::class);

        Route::prefix('/entities')->group(function () {
            Route::apiResource('/', EntityController::class)
            ->parameter('', 'entity');
            Route::post('/uploadUrl', [EntityController::class, 'signedUrl']);
            Route::post('/{entity}/upload', [EntityController::class, 'confirmUpload']);
        });

        Route::apiResource('/entityTypes', EntityTypesController::class);
        Route::apiResource('/incidentTypes', IncidentTypeController::class)->only(['index', 'store', 'show']);
        Route::apiResource('/incidentStates', IncidentStateController::class);
        Route::apiResource('/incidentPriorities', IncidentPriorityController::class);
        Route::apiResource('/incidents', IncidentController::class);
        Route::prefix('incidents/{incident}')->group(function () {
            // PCO
            Route::get('/pco', [IncidentPCOController::class, 'index']);
            Route::post('/pco', [IncidentPCOController::class, 'store']);
            Route::put('/pco/{pco}', [IncidentPCOController::class, 'update'])->whereNumber('pco');
            Route::patch('/pco/{pco}', [IncidentPCOController::class, 'update'])->whereNumber('pco');
            // LOGISTICA
            Route::get('/parties', [IncidentPartyController::class, 'index']);
            Route::post('/parties', [IncidentPartyController::class, 'store']);
            Route::put('/parties/{party}', [IncidentPartyController::class, 'update']);
            Route::patch('/parties/{party}', [IncidentPartyController::class, 'update']);
            // TIMELINE COMMENTS
            Route::post('/comments', [TimelineCommentController::class, 'store']);
            Route::put('/comments/{comment}', [TimelineCommentController::class, 'update']);
            // TIMELINE
            if (! function_exists('transformActivityValues')) {
                function transformActivityValues(array $values): array
                {
                    foreach ($values as $field => &$value) {
                        if ($value === null) {
                            continue;
                        }

                        $dateFields = [
                            'start_datetime',
                            'end_datetime',
                            'activation_pco_datetime',
                            'start_pco_datetime',
                            'end_pco_datetime',
                        ];

                        if (in_array($field, $dateFields) && !empty($value)) {
                            $value = Carbon::parse($value)->format('d/m/Y H:i');
                        }

                        switch ($field) {
                            case 'incident_type_id':
                                $value = IncidentType::withTrashed()->find($value)?->code ?? $value;
                                break;
                            case 'incident_state_id':
                                $value = IncidentState::find($value)?->name ?? $value;
                                break;
                            case 'incident_priority_id':
                                $value = IncidentPriority::find($value)?->description ?? $value;
                                break;
                            case 'entity_id':
                                $value = Entity::find($value)?->name ?? $value;
                                break;
                        }
                    }
                    return $values;
                }
            }

            Route::get('/timeline', function (Incident $incident) {
                $logs = Activity::query()
                    ->where(function ($q) use ($incident) {
                        $q->where('subject_type', Incident::class)->where('subject_id', $incident->id);
                    })
                    ->orWhere(function ($q) use ($incident) {
                        $q->where('subject_type', IncidentPCO::class)->whereIn('subject_id', function ($sub) use ($incident) {
                            $sub->select('id')->from('incident_pcos')->where('incident_id', $incident->id);
                        });
                    })
                    ->orWhere(function ($q) use ($incident) {
                        $q->where('subject_type', IncidentParty::class)->whereIn('subject_id', function ($sub) use ($incident) {
                            $sub->select('id')->from('incident_parties')->where('incident_id', $incident->id);
                        });
                    })
                    ->with('causer')->get()
                    ->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'type' => 'log',
                            'module' => $a->log_name,
                            'event' => $a->description,
                            'user' => $a->causer?->name,
                            'date' => $a->created_at->toISOString(),
                            'changes' => transformActivityValues($a->attribute_changes['attributes'] ?? []),
                            'old_values' => transformActivityValues($a->attribute_changes['old'] ?? []),
                            'body' => null,
                            'comment_id' => null,
                        ];
                    });

                $comments = $incident->comments()
                    ->with('user')->get()
                    ->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'type' => 'comment',
                            'module' => 'comments',
                            'event' => 'acrescentou uma entrada',
                            'user' => $comment->user?->name,
                            'body' => $comment->body,
                            'date' => $comment->created_at->toISOString(),
                            'changes' => null,
                            'old_values' => null,
                            'comment_id' => $comment->id,
                        ];
                    });

                $timeline = $logs->concat($comments)->sortByDesc('date')->values();

                return response()->json($timeline);
            });
        });

        Route::prefix('/facilities')->group(function () {
            Route::apiResource('/', FacilitiesController::class)->parameter('', 'facility');
            // IMAGEM
            Route::post('/uploadUrl', [FacilitiesController::class, 'signedUrl']);
            Route::post('/{facility}/upload', [FacilitiesController::class, 'confirmUpload']);
            // DOCUMENTOS
            Route::post('/{facility}/documents', [FacilitiesController::class, 'uploadDocuments']);
            Route::get('/{facility}/documents/{mediaId}/download', [FacilitiesController::class, 'downloadDocument']);
            Route::delete('/{facility}/documents/{mediaId}', [FacilitiesController::class, 'deleteDocument']);
        });
    });
});
