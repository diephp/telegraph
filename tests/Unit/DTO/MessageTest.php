<?php

use DefStudio\Telegraph\DTO\Message;

it('parses the message fields used by commands and callbacks', function () {
    $message = Message::fromArray([
        'message_id' => 42,
        'message_thread_id' => 7,
        'date' => 1700000000,
        'text' => '/start connect-resource',
        'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'Ana', 'language_code' => 'es'],
        'chat' => ['id' => -1001, 'type' => 'supergroup', 'title' => 'Kitchen'],
        'reply_markup' => ['inline_keyboard' => [[['text' => 'Accept', 'callback_data' => 'action:accept;id:42']]]],
    ]);

    expect($message->id())->toBe(42)
        ->and($message->messageThreadId())->toBe(7)
        ->and($message->text())->toBe('/start connect-resource')
        ->and($message->chat()->id())->toBe('-1001')
        ->and($message->from()->languageCode())->toBe('es')
        ->and($message->keyboard()->toArray()[0][0]['callback_data'])->toBe('action:accept;id:42')
        ->and($message->toArray()['chat']['title'])->toBe('Kitchen');
});

it('ignores unsupported media and payment fields without breaking text handling', function () {
    $message = Message::fromArray([
        'message_id' => 42,
        'date' => 1700000000,
        'caption' => 'A caption',
        'photo' => [['file_id' => 'unused']],
        'successful_payment' => ['currency' => 'EUR'],
    ]);

    expect($message->text())->toBe('A caption')
        ->and($message->newChatMembers())->toBeEmpty()
        ->and($message->entities())->toBeEmpty()
        ->and($message->toArray())->not->toHaveKey('photos');
});
