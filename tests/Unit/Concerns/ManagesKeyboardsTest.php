<?php

/** @noinspection PhpUnhandledExceptionInspection */

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Telegraph;

it('can add a keyboard to a message', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->html('foobar')
            ->keyboard(Keyboard::make()->button('foo')->url('bar'));
    })->toMatchTelegramSnapshot();
});

it('can add a keyboard as an array', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->html('foobar')
            ->keyboard([
                [
                    ['text' => 'foo', 'url' => 'bar'],
                ],
            ]);
    })->toMatchTelegramSnapshot();
});

it('can add a keyboard as a closure', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->html('foobar')
            ->keyboard(fn ($keyboard) => $keyboard->button('foo')->url('bar'));
    })->toMatchTelegramSnapshot();
});

it('can replace the keyboard of a message', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->replaceKeyboard('123456', Keyboard::make()->buttons([
            Button::make('foo')->url('bar'),
        ]));
    })->toMatchTelegramSnapshot();
});

it('can request to remove a reply keyboard', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->html('foobar')
            ->removeReplyKeyboard();
    })->toMatchTelegramSnapshot();
});

it('can request to remove a reply keyboard selectively', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->html('foobar')
            ->removeReplyKeyboard(true);
    })->toMatchTelegramSnapshot();
});

it('can force a reply', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->message('foobar')->forceReply();
    })->toMatchTelegramSnapshot();
});

it('can force a reply with placeholder', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->message('foobar')->forceReply('enter your answer...');
    })->toMatchTelegramSnapshot();
});

it('can force a reply selectively', function () {
    expect(function (Telegraph $telegraph) {
        return $telegraph->message('foobar')->forceReply(selective: true);
    })->toMatchTelegramSnapshot();
});
