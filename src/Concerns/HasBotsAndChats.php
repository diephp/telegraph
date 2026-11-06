<?php

/** @noinspection DuplicatedCode */

/** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace DefStudio\Telegraph\Concerns;

use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\ScopedPayloads\SetChatMenuButtonPayload;
use DefStudio\Telegraph\Telegraph;

/**
 * @mixin Telegraph
 */
trait HasBotsAndChats
{
    protected TelegraphBot|string|null $bot = null;

    protected TelegraphChat|string|null $chat = null;

    public function bot(TelegraphBot|string $bot): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->bot = $bot;

        return $telegraph;
    }

    public function chat(TelegraphChat|string $chat): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->chat = $chat;

        if (empty($telegraph->bot) && $chat instanceof TelegraphChat) {
            $telegraph->bot = $chat->bot;
        }

        return $telegraph;
    }

    protected function getBotIfAvailable(): TelegraphBot|string|null
    {
        $telegraph = clone $this;

        if (empty($telegraph->bot)) {
            /** @var TelegraphBot|string $bot */
            $bot = rescue(fn () => TelegraphBot::query()->sole(), config('telegraph.bot_token'), false);

            $telegraph->bot = $bot;
        }

        return $telegraph->bot;
    }

    protected function getBot(): TelegraphBot|string
    {
        return $this->getBotIfAvailable() ?? throw TelegraphException::missingBot();
    }

    protected function getBotToken(): string
    {
        $bot = $this->getBot();

        if ($bot instanceof TelegraphBot) {
            return $bot->token;
        }

        return $bot;
    }

    protected function getChatIfAvailable(): TelegraphChat|string|null
    {
        $telegraph = clone $this;

        if (empty($telegraph->chat)) {
            $bot = $telegraph->getBotIfAvailable();

            if ($bot instanceof TelegraphBot) {
                $telegraph->chat = rescue(fn () => $bot->chats()->sole(), report: false);
            }
        }

        if (empty($telegraph->chat)) {
            $telegraph->chat = rescue(fn () => TelegraphChat::query()->sole(), report: false);
        }

        return $telegraph->chat ?? null;
    }

    protected function getChat(): TelegraphChat|string
    {
        return $this->getChatIfAvailable() ?? throw TelegraphException::missingChat();
    }

    protected function getChatId(): string
    {
        $chat = $this->getChat();

        if ($chat instanceof TelegraphChat) {
            return $chat->chat_id;
        }

        return $chat;
    }

    protected function syncChatIdData(): void
    {
        $chat = $this->getChatIfAvailable();

        if ($chat === null) {
            return;
        }

        $this->data['chat_id'] = $chat instanceof TelegraphChat
            ? $chat->chat_id
            : $chat;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function prepareChatData(array $data): array
    {
        $chat = $this->getChatIfAvailable();
        $shouldSetChatId = array_key_exists('chat_id', $data) || $this->endpointRequiresChat();

        if ($chat !== null && $shouldSetChatId) {
            $data['chat_id'] = $chat instanceof TelegraphChat
                ? $chat->chat_id
                : $chat;

            return $data;
        }

        if ($this->endpointRequiresChat() && (!isset($data['chat_id']) || $data['chat_id'] === '')) {
            throw TelegraphException::missingChat();
        }

        return $data;
    }

    protected function endpointRequiresChat(): bool
    {
        return in_array($this->endpoint ?? null, [
            self::ENDPOINT_REPLACE_KEYBOARD,
            self::ENDPOINT_MESSAGE,
            self::ENDPOINT_DELETE_MESSAGE,
            self::ENDPOINT_DELETE_MESSAGES,
            self::ENDPOINT_PIN_MESSAGE,
            self::ENDPOINT_UNPIN_MESSAGE,
            self::ENDPOINT_UNPIN_ALL_MESSAGES,
            self::ENDPOINT_EDIT_MESSAGE,
            self::ENDPOINT_GET_CHAT_INFO,
            self::ENDPOINT_FORWARD_MESSAGE,
            self::ENDPOINT_COPY_MESSAGE,
        ], true);
    }

    public function botInfo(): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_GET_BOT_INFO;

        return $telegraph;
    }

    public function chatInfo(): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_GET_CHAT_INFO;
        $telegraph->syncChatIdData();

        return $telegraph;
    }

    public function setChatMenuButton(): SetChatMenuButtonPayload
    {
        $telegraph = clone $this;
        $telegraph->endpoint = self::ENDPOINT_SET_CHAT_MENU_BUTTON;

        if ($this->getChatIfAvailable() !== null) {
            $telegraph->syncChatIdData();
        }

        return SetChatMenuButtonPayload::makeFrom($telegraph);
    }

    public function chatMenuButton(): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_GET_CHAT_MENU_BUTTON;

        return $telegraph;
    }
}
