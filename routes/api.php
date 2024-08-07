<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/webhook", [WebhookController::class, 'eventNotificationsHandler']);
Route::post("/webhook/subscribe", [WebhookController::class, 'subscribeWebhook']);
Route::get("/webhook", [WebhookController::class, 'verificationRequestHandler']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
