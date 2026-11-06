<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection LaravelFunctionsInspection */

use DefStudio\Telegraph\Facades\Telegraph;

it('can customize the destination bot', function () {
    withfakeUrl();
    $bot = make_bot();

    $telegraph = Telegraph::bot($bot)
        ->registerWebhook();

    expect($telegraph->getApiUrl())->toStartWith("https://api.telegram.org/bot$bot->token/");
});

it('can customize the destination bot through its token', function () {
    withfakeUrl();

    $telegraph = Telegraph::bot('TOKEN')
        ->registerWebhook();

    expect($telegraph->getApiUrl())->toStartWith("https://api.telegram.org/botTOKEN/");
});

it('can customize the destination chat', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())
            ->html('foobar');
    })->toMatchTelegramSnapshot();
});

it('can customize the destination chat through its ID', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat("123456789")
            ->html('foobar');
    })->toMatchTelegramSnapshot();
});

it('can retrieve bot info', function () {
    Telegraph::fake();
    $bot = make_bot();

    $response = Telegraph::bot($bot)->botInfo()->send();
    expect($response->json('result'))->toMatchSnapshot();
});

it('can retrieve bot info from its token', function () {
    Telegraph::fake();
    $response = Telegraph::bot("3f3814e1-5836-3d77-904e-60f64b15df36")->botInfo()->send();
    expect($response->json('result'))->toMatchSnapshot();
});

it('can retrieve chat info', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())->chatInfo();
    })->toMatchTelegramSnapshot();
});

it('can restore default chat menu button', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())
            ->setChatMenuButton()->default();
    })->toMatchTelegramSnapshot();
});

it('can set commands chat menu button', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())
            ->setChatMenuButton()->commands();
    })->toMatchTelegramSnapshot();
});

it('can set commands bot menu button', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph
            ->setChatMenuButton()->commands();
    })->toMatchTelegramSnapshot();
});

it('can set web app chat menu button', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())
            ->setChatMenuButton()->webApp("VISIT", "https://my-web.app");
    })->toMatchTelegramSnapshot();
});

it('can retrieve chat menu button', function () {
    expect(function (\DefStudio\Telegraph\Telegraph $telegraph) {
        return $telegraph->chat(make_chat())
            ->chatMenuButton();
    })->toMatchTelegramSnapshot();
});
