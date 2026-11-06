<?php

use function Pest\Laravel\post;

test('bots can be bound by token', function () {
    $bot = bot();
    post("/telegraph/$bot->token/webhook", webhook_request('test')->all(), ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])
        ->assertNoContent();
});

test('invalid tokens are rejected', function () {
    post("/telegraph/123456/webhook", webhook_request('test')->all(), ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])
        ->assertNotFound();
});
