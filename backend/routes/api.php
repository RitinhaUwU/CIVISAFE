<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Donations\DonationAuditController;
use App\Http\Controllers\Donations\DonationDistributionController;
use App\Http\Controllers\Donations\DonationGoodsTypeController;
use App\Http\Controllers\Donations\DonationInventoryManagementController;
use App\Http\Controllers\Donations\DonationLogController;
use App\Http\Controllers\Donations\DonationStatsController;
use App\Http\Controllers\Donations\DonationStockController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityTypesController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\Incidents\IncidentController;
use App\Http\Controllers\Incidents\IncidentPartyController;
use App\Http\Controllers\Incidents\IncidentPCOController;
use App\Http\Controllers\Incidents\IncidentPriorityController;
use App\Http\Controllers\Incidents\IncidentStateController;
use App\Http\Controllers\Incidents\IncidentTimelineController;
use App\Http\Controllers\Incidents\IncidentTypeController;
use App\Http\Controllers\Incidents\TimelineCommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

if (!app()->environment('production')) {
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
}

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

        Route::prefix('/donationGoodsTypes')->group(function () {
            Route::get('/all', [DonationGoodsTypeController::class, 'all']);
            Route::apiResource('/', DonationGoodsTypeController::class)
                ->parameter('', 'donationGoodsType');
        });

        Route::prefix('/donations')->group(function () {
            Route::apiResource('/audit', DonationAuditController::class)
                ->only(['index', 'store']);
            Route::get('/stats', [DonationStatsController::class, 'stats']);
            Route::get('/stock', [DonationStockController::class, 'stock']);

            Route::prefix('/distributions')->group(function () {
                Route::get('/{donationDistribution}/audit', [DonationDistributionController::class, 'audit']);

                Route::apiResource('/', DonationDistributionController::class)
                    ->parameter('', 'donationDistribution')
                    ->except(['destroy']);
            });

            Route::get('/{donationLog}/audit', [DonationLogController::class, 'audit']);

            Route::apiResource('/', DonationLogController::class)
                ->parameter('', 'donationLog')
                ->except(['destroy']);
        });
    });
});
