<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\company\CompanyTrelloController;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/auth/me', function (Request $request) {
        return $request->user();
    });
});

Route::match(['head', 'get'], '/trello/webhook/{integration}', [CompanyTrelloController::class, 'webhookHead'])
    ->name('trello.webhook.head');
Route::post('/trello/webhook/{integration}', [CompanyTrelloController::class, 'webhook'])
    ->name('trello.webhook');
