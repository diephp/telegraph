<?php

use DefStudio\Telegraph\Controllers\WebhookController;
use DefStudio\Telegraph\Telegraph;
use DefStudio\Telegraph\Tests\Support\TestWebhookHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('rejects missing or invalid webhook secrets before looking up a bot', function (?string $header) {
    config(['telegraph.webhook.secret' => 'expected-secret']);
    $request = Request::create('/', 'POST');
    if ($header !== null) {
        $request->headers->set('X-Telegram-Bot-Api-Secret-Token', $header);
    }

    try {
        app(WebhookController::class)->handle($request, 'does-not-exist');
        test()->fail('An invalid webhook secret was accepted');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
})->with([null, '', 'wrong-secret']);

it('accepts the configured secret and dispatches the command', function () {
    config(['telegraph.webhook.secret' => 'expected-secret']);
    $bot = bot();
    $request = webhook_command('/test');
    $request->headers->set('X-Telegram-Bot-Api-Secret-Token', 'expected-secret');

    $response = app(WebhookController::class)->handle($request, $bot->token);

    expect($response->status())->toBe(204)->and(TestWebhookHandler::$calls_count)->toBe(1);
});

it('uses the same secret when registering the webhook', function () {
    withfakeUrl();
    config(['telegraph.webhook.secret' => 'expected-secret']);
    $request = app(Telegraph::class)->bot(bot())->registerWebhook()->toArray();

    expect($request['payload']['secret_token'])->toBe('expected-secret');
});
