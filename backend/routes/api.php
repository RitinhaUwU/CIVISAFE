<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityTypesController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\IncidentController;
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

    Artisan::call('migrate:fresh');
    Artisan::call('db:seed');
    return response()->noContent();
});

Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);

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
        Route::apiResource('/entities', EntityController::class);
        Route::apiResource('/entityTypes', EntityTypesController::class);
        Route::apiResource('/incidentTypes', IncidentTypeController::class)->only(['index', 'store', 'show']);
        Route::apiResource('/incidentStates', IncidentStateController::class);
        Route::apiResource('/incidentPriorities', IncidentPriorityController::class);
        Route::apiResource('/incidents', IncidentController::class);
        Route::apiResource('/facilities', FacilitiesController::class);
    });
});
