<?php

use DefStudio\Telegraph\Controllers\WebhookController;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Storage\CacheStorageDriver;
use DefStudio\Telegraph\Storage\FileStorageDriver;
use DefStudio\Telegraph\Tests\Support\TestWebhookHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('fails closed when no webhook secret is configured', function () {
    config(['telegraph.webhook.secret' => null, 'telegraph.webhook.secret_token' => null]);

    try {
        app(WebhookController::class)->handle(webhook_command('/test'), 'irrelevant');
        test()->fail('The webhook was accepted without authentication configuration');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(503);
    }
});

it('does not register an unauthenticated webhook', function () {
    config(['telegraph.webhook.secret' => null, 'telegraph.webhook.secret_token' => null]);
    expect(fn () => Telegraph::bot('test-token')->registerWebhook())
        ->toThrow(TelegraphException::class, 'TELEGRAPH_WEBHOOK_SECRET');
});

it('does not expose unregistered public helpers as commands or callbacks', function () {
    $handler = new class () extends TestWebhookHandler {
        public bool $called = false;

        public function internal_helper(): void
        {
            $this->called = true;
        }
    };
    $bot = bot();
    Telegraph::fake();

    $handler->handle(webhook_command('/internal_helper'), $bot);
    $handler->handle(webhook_request('internal_helper'), $bot);

    expect($handler->called)->toBeFalse();
    Telegraph::assertRepliedWebhook('Invalid action');
});

it('clears callback state when the same handler serves another bot', function () {
    $first = bot('first-token');
    $second = bot('second-token');
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);
    $handler = app(TestWebhookHandler::class);

    $handler->handle(webhook_request('send_reply'), $first);
    $handler->handle(webhook_command('/reply_to_command'), $second);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'botfirst-token/answerCallbackQuery'));
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'botsecond-token/sendMessage') && $request['text'] === 'foo');
    Http::assertSentCount(2);
});

it('never uses another bots only chat as an implicit destination', function () {
    bot('first-token');
    $second = TelegraphBot::factory()->create(['token' => 'second-token']);
    Http::fake();

    expect(fn () => Telegraph::bot($second)->html('Private order')->send())
        ->toThrow(TelegraphException::class, 'No TelegraphChat defined');
    Http::assertNothingSent();
});

it('does not serialize bot credentials or expose them in safe api urls', function () {
    $bot = make_bot();
    $url = Telegraph::bot($bot)->html('Order')->getSafeApiUrl();

    expect($bot->toArray())->not->toHaveKey('token')
        ->and($url)->not->toContain($bot->token)
        ->and($url)->toContain('[redacted]/sendMessage');
});

it('rejects endpoints containing url components', function (string $endpoint) {
    expect(fn () => Telegraph::withEndpoint($endpoint))->toThrow(TelegraphException::class);
})->with(['../sendMessage', 'sendMessage?chat_id=1', 'sendMessage#fragment', 'sendMessage/other']);

it('rejects url components inside bot credentials before sending', function (string $token) {
    Http::fake();
    expect(fn () => Telegraph::bot($token)->chat('42')->html('Order')->send())
        ->toThrow(TelegraphException::class, 'invalid characters');
    Http::assertNothingSent();
})->with(['../other-bot', 'token?query=value', 'token#fragment', 'token%2Fother']);

it('redacts credentials from transport exceptions', function () {
    Http::fake(['*' => Http::failedConnection('Could not connect to https://api.telegram.org/botsecret-token/sendMessage')]);

    try {
        Telegraph::html('Order')->bot('secret-token')->chat('42')->send();
        test()->fail('A connection exception was expected');
    } catch (TelegraphException $exception) {
        expect($exception->getMessage())->not->toContain('secret-token')
            ->and($exception->getPrevious())->toBeNull();
    }
});

it('disables redirects on credential-bearing api requests', function () {
    Http::fake(function (Request $request, array $options) {
        expect($options['allow_redirects'])->toBeFalse();

        return Http::response('', 302, ['Location' => 'https://untrusted.example']);
    });
    $response = Telegraph::html('Order')->bot('test-token')->chat('42')->send();

    expect($response->status())->toBe(302);
    Http::assertSentCount(1);
});

it('treats user-supplied model markers as plain storage data', function () {
    $driver = new CacheStorageDriver('Demo', '1', ['key_prefix' => 'security', 'store' => null]);
    $payload = [':tgph_model_class:' => stdClass::class, ':tgph_model_id:' => 1];

    $driver->set('payload', $payload);

    expect($driver->get('payload'))->toBe($payload);
    $driver->storeData('invalid', $payload);
    expect(fn () => $driver->get('invalid'))->toThrow(UnexpectedValueException::class);
});

it('keeps custom storage keys inside their model directory', function () {
    Storage::fake();
    $driver = new FileStorageDriver('Demo', '../outside', config('telegraph.storage.stores.file'));
    $driver->set('value', 'safe');

    expect(Storage::exists('telegraph/Demo/..%2Foutside.json'))->toBeTrue()
        ->and(Storage::exists('telegraph/outside.json'))->toBeFalse();
});
