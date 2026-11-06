<?php

/** @noinspection PhpUnhandledExceptionInspection */

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Support\Testing\Fakes\TelegraphSetChatMenuButtonFake;
use Illuminate\Support\Str;

test('name is set to ID if missing', function () {
    $bot = TelegraphBot::create([
        'token' => Str::uuid(),
    ]);

    $chat = $bot->chats()->create(['chat_id' => Str::uuid()]);

    expect($chat->name)->toBe("Chat #$chat->id");
});

it('can send a text message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->message('foo')->send();

    Telegraph::assertSent('foo');
});

it('can send an html message', function () {
    $chat = make_chat();

    $telegraph = $chat->html('foo');

    expect($telegraph->toArray())->toMatchSnapshot();
});

it('can send a markdown message', function () {
    $chat = make_chat();

    $telegraph = $chat->markdown('foo');

    expect($telegraph->toArray())->toMatchSnapshot();
});

it('can send a markdownV2 message', function () {
    $chat = make_chat();

    $telegraph = $chat->markdownV2('foo');

    expect($telegraph->toArray())->toMatchSnapshot();
});

it('can replace a keyboard', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->replaceKeyboard(123456, Keyboard::make()->buttons([
        Button::make('test')->url('aaa'),
    ]))->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_REPLACE_KEYBOARD, [
        'reply_markup' => [
            'inline_keyboard' => [
                [
                    ['text' => 'test', 'url' => 'aaa'],
                ],
            ],
        ],
    ]);
});

it('can delete a keyboard', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->deleteKeyboard(123456)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_REPLACE_KEYBOARD, [
        'reply_markup' => '',
    ]);
});

it('can delete a message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->deleteMessage(42)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_DELETE_MESSAGE, [
        'message_id' => 42,
    ], false);
});

it('can delete messages', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->deleteMessages([23, 42])->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_DELETE_MESSAGES, [
        'message_ids' => [23, 42],
    ]);
});

it('can pin a message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->pinMessage(42)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_PIN_MESSAGE, [
        'message_id' => 42,
    ], false);
});

it('can unpin a message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->unpinMessage(42)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_UNPIN_MESSAGE, [
        'message_id' => 42,
    ], false);
});

it('can unpin all messages', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->unpinAllMessages()->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_UNPIN_ALL_MESSAGES);
});

it('can retrieve its telegram info', function () {
    Telegraph::fake();
    $chat = make_chat();

    expect($chat->info())->toMatchSnapshot();
});

it('can forward a message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->forwardMessage($chat, 123)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_FORWARD_MESSAGE, [
        'from_chat_id' => $chat->chat_id,
        'message_id' => 123,
    ]);
});

it('can copy a message', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->copyMessage($chat, 123)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_COPY_MESSAGE, [
        'from_chat_id' => $chat->chat_id,
        'message_id' => 123,
    ]);
});

it('can retrieve current chat menu button', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->menuButton()->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_GET_CHAT_MENU_BUTTON);
});

it('can restore default menu button', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->setMenuButton()->default()->send();

    TelegraphSetChatMenuButtonFake::assertChangedMenuButton('default');
});

it('can set commands menu button', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->setMenuButton()->commands()->send();

    TelegraphSetChatMenuButtonFake::assertChangedMenuButton('commands');
});

it('can set web app menu button', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->setMenuButton()->webApp("VISIT", "https://my-web.app")->send();

    TelegraphSetChatMenuButtonFake::assertChangedMenuButton('web_app', [
        'text' => "VISIT",
        'web_app' => [
            'url' => "https://my-web.app",
        ],
    ]);
});

it('can send request with custom endpoint and data', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->withEndpoint('custom-endpoint')->withData('sample', 'test')->send();

    Telegraph::assertSentData('custom-endpoint', [
        'sample' => 'test',
    ]);
});

it('can send a message to a specific Thread after', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->message('foo')->inThread(5)->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE, [
        'text' => 'foo',
        'message_thread_id' => 5,
    ]);
});

it('can send a message to a specific Thread before', function () {
    Telegraph::fake();
    $chat = make_chat();

    $chat->inThread(5)->message('foo')->send();

    Telegraph::assertSentData(\DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE, [
        'text' => 'foo',
        'message_thread_id' => 5,
    ]);
});
