<?php

use Sixgweb\InstagramMedia\Handlers\WebhooksHandler;
use Sixgweb\InstagramMedia\Handlers\AuthorizationHandler;

Route::prefix('/api/sixgweb/instagrammedia')->middleware('web')->group(function () {
    Route::get('/authorize', [AuthorizationHandler::class, 'authorize']);
    Route::get('/refresh', [AuthorizationHandler::class, 'refresh']);
    Route::post('/webhooks', [WebhooksHandler::class, 'handle']);
    Route::get('/webhooks', [WebhooksHandler::class, 'success']);
});
