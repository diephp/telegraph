<?php

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('edits a message with a late destination and preserves the original builder', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 91]])]);
    $message = Telegraph::html('Updated order')->bot('bot-a')->chat('-1001');
    $response = $message->edit(91)->bot('bot-b')->chat('-1002')->inThread(7)->send();
    $message->send();

    expect($response->telegraphMessageId())->toBe(91);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'bot-b/editMessageText')
        && $request['chat_id'] === '-1002' && $request['message_id'] === 91 && $request['message_thread_id'] === 7);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'bot-a/sendMessage')
        && $request['chat_id'] === '-1001' && !isset($request['message_id']));
    Http::assertSentCount(2);
});

it('exposes rejected telegram requests without inventing a message id', function (int $status) {
    Http::fake(['*' => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'bot was blocked'], $status)]);
    $response = Telegraph::html('Order')->bot('test-bot')->chat('42')->send();

    expect($response->telegraphError())->toBeTrue()
        ->and($response->telegraphMessageId())->toBeNull()
        ->and($response->json('description'))->toBe('bot was blocked');
})->with([200, 403]);

it('preserves opaque callback identifiers and answers the clicked button', function () {
    $bot = bot();
    $request = webhook_request('send_reply');
    $payload = $request->input('callback_query');
    $payload['id'] = 'opaque-callback-id';
    $request->merge(['callback_query' => $payload]);
    Http::fake(['*' => Http::response(['ok' => true])]);

    app(\DefStudio\Telegraph\Tests\Support\TestWebhookHandler::class)->handle($request, $bot);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery')
        && $request['callback_query_id'] === 'opaque-callback-id' && $request['text'] === 'foo');
    Http::assertSentCount(1);
});

it('rejects a missing destination before sending a delete request', function () {
    Http::fake();
    expect(fn () => Telegraph::bot('test-bot')->deleteMessage(42)->send())
        ->toThrow(\DefStudio\Telegraph\Exceptions\TelegraphException::class, 'No TelegraphChat defined');
    Http::assertNothingSent();
});

it('rejects a missing bot before making an http request', function () {
    config(['telegraph.bot_token' => null]);
    Http::fake();
    expect(fn () => Telegraph::html('Order')->chat('42')->send())
        ->toThrow(\DefStudio\Telegraph\Exceptions\TelegraphException::class, 'No TelegraphBot defined');
    Http::assertNothingSent();
});
