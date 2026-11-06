<?php

/** @noinspection PhpUnhandledExceptionInspection */

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('name is set to ID if missing', function () {
    $bot = TelegraphBot::create([
        'token' => Str::uuid(),
    ]);

    expect($bot->name)->toBe("Bot #$bot->id");
});

it('can retrieve its telegram info', function () {
    Telegraph::fake();
    $bot = make_bot();

    expect($bot->info())->toMatchSnapshot();
});

it('can retrieve its url', function () {
    Telegraph::fake();
    $bot = make_bot();

    expect($bot->url())->toMatchSnapshot();
});

it('can send request with custom endpoint and data', function () {
    withfakeUrl();

    Telegraph::fake();
    $bot = make_bot();

    $bot->withEndpoint('custom-endpoint')->withData('sample', 'test')->send();

    Telegraph::assertSentData('custom-endpoint', [
        'sample' => 'test',
    ]);
});

it('can register its webhook', function () {
    withfakeUrl();

    Telegraph::fake();
    $bot = make_bot();

    $bot->registerWebhook()->send();

    Telegraph::assertRegisteredWebhook();
});

it('can register its webhook dropping pending updates', function () {
    withfakeUrl();

    Telegraph::fake();
    $bot = make_bot();

    $bot->registerWebhook(dropPendingUpdates: true)->send();

    Telegraph::assertRegisteredWebhook([
        'drop_pending_updates' => true,
    ], false);
});

it('can register its webhook settings its max connections', function () {
    withfakeUrl();

    Telegraph::fake();
    $bot = make_bot();

    $bot->registerWebhook(maxConnections: 34)->send();

    Telegraph::assertRegisteredWebhook([
        'max_connections' => 34,
    ], false);
});

it('can register its webhook settings its secret token', function () {
    withfakeUrl();

    Telegraph::fake();
    $bot = make_bot();

    $bot->registerWebhook(secretToken: 'bar')->send();

    Telegraph::assertRegisteredWebhook([
        'secret_token' => 'bar',
    ], false);
});

it('can unregister its webhook', function () {
    Telegraph::fake();
    $bot = make_bot();

    $bot->unregisterWebhook()->send();

    Telegraph::assertUnregisteredWebhook();
});

it('can register commands', function () {
    Telegraph::fake();

    $bot = make_bot();

    $bot->registerCommands(['foo' => 'bar'])->send();

    Telegraph::assertSentData(
        \DefStudio\Telegraph\Telegraph::ENDPOINT_REGISTER_BOT_COMMANDS,
        [
            'commands' => [
                ['command' => 'foo', 'description' => 'bar'],
            ],
        ]
    );
});

it('can get registered commands', function () {
    Telegraph::fake();

    $bot = make_bot();

    $bot->getRegisteredCommands()->send();

    Telegraph::assertSentData(
        \DefStudio\Telegraph\Telegraph::ENDPOINT_GET_REGISTERED_BOT_COMMANDS,
        []
    );
});

it('can unregister commands', function () {
    Telegraph::fake();

    $bot = make_bot();

    $bot->unregisterCommands()->send();

    Telegraph::assertSentData(
        \DefStudio\Telegraph\Telegraph::ENDPOINT_UNREGISTER_BOT_COMMANDS,
        []
    );
});

it('can get its webhook debug info', function () {
    Telegraph::fake();
    $bot = make_bot();

    $bot->getWebhookDebugInfo()->send();

    Telegraph::assertRequestedWebhookDebugInfo();
});

it('can reply a webhook call', function () {
    Telegraph::fake();
    $bot = make_bot();

    $bot->replyWebhook(1231456, 'hello')->send();

    Telegraph::assertRepliedWebhook('hello');
});
