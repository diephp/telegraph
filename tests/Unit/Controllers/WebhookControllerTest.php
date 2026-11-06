<?php

/** @noinspection PhpUnhandledExceptionInspection */

use DefStudio\Telegraph\Controllers\WebhookController;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Tests\Support\TestWebhookHandler;
use Illuminate\Database\Eloquent\Collection;

it('calls configured handler', function () {
    $bot = bot();
    $bot->setRelation('chats', Collection::make([TelegraphChat::factory(['chat_id' => '-123456789'])->make()]));

    $request = webhook_request('test');
    $request->headers->set('X-Telegram-Bot-Api-Secret-Token', 'test-secret');
    $response = app(WebhookController::class)->handle($request, $bot->token);

    expect(TestWebhookHandler::$calls_count)->toBe(1);

    expect($response->status())->toBe(204);
});
