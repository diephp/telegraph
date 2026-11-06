<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUnused */

/** @noinspection PhpPropertyOnlyWrittenInspection */

namespace DefStudio\Telegraph\Support\Testing\Fakes;

use DefStudio\Telegraph\Concerns\FakesRequests;
use DefStudio\Telegraph\ScopedPayloads\SetChatMenuButtonPayload;
use DefStudio\Telegraph\Telegraph;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

class TelegraphFake extends Telegraph
{
    use FakesRequests;

    /**
     * @param array<string, array> $replies
     */
    public function __construct(array $replies = [])
    {
        parent::__construct();
        $this->replies = $replies;
    }

    public function prepareForNewRequest(): void
    {
        $this->files = new Collection();
    }

    protected function inheritState(Telegraph $fake): Telegraph
    {
        $fake->bot = $this->bot;
        $fake->chat = $this->chat;
        $fake->data = $this->data;
        $fake->baseUrl = $this->baseUrl;
        $fake->httpProxy = $this->httpProxy;

        return $fake;
    }

    public function setChatMenuButton(): SetChatMenuButtonPayload
    {
        $fake = $this->inheritState(new TelegraphSetChatMenuButtonFake($this->replies));
        $fake->endpoint = self::ENDPOINT_SET_CHAT_MENU_BUTTON;

        return $fake;
    }

    public function assertSent(string $message, bool $exact = true): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_MESSAGE, [
            'text' => $message,
        ], $exact);
    }

    public function assertNotSent(string $message, bool $exact = true): void
    {
        $this->assertNotSentData(Telegraph::ENDPOINT_MESSAGE, [
            'text' => $message,
        ], $exact);
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty(self::$sentMessages, sprintf("Failed to assert that no request were sent (sent %d requests so far)", count(self::$sentMessages)));
    }

    public function assertRegisteredWebhook(array $data = null, bool $exact = true): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_SET_WEBHOOK, $data, $exact);
    }

    public function assertUnregisteredWebhook(array $data = null, bool $exact = true): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_UNSET_WEBHOOK, $data, $exact);
    }

    public function assertRequestedWebhookDebugInfo(array $data = null, bool $exact = true): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_GET_WEBHOOK_DEBUG_INFO, $data, $exact);
    }

    public function assertRepliedWebhook(string $message): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_ANSWER_WEBHOOK, [
            'text' => $message,
        ]);
    }

    public function assertRepliedWebhookIsAlert(): void
    {
        $this->assertSentData(Telegraph::ENDPOINT_ANSWER_WEBHOOK, [
            'show_alert' => true,
        ]);
    }
}
