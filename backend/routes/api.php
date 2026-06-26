<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityTypesController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\IncidentTimelineController;
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
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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
            Route::get('/timeline', [IncidentTimelineController::class, 'index']);
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
