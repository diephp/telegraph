<?php

/** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnused */

/** @noinspection PhpUnhandledExceptionInspection */

namespace DefStudio\Telegraph\Handlers;

use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Chat;
use DefStudio\Telegraph\DTO\ChatMemberUpdate;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\DTO\User;
use DefStudio\Telegraph\Exceptions\TelegramWebhookException;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class WebhookHandler
{
    protected TelegraphBot $bot;
    protected ?TelegraphChat $chat = null;

    /** @var list<string> Methods that may be called by commands and callback data. */
    protected array $allowedActions = [];

    protected int $messageId = 0;
    protected int|string|null $callbackQueryId = null;

    protected Request $request;
    protected ?Message $message = null;
    protected ?CallbackQuery $callbackQuery = null;
    protected ?ChatMemberUpdate $myChatMember = null;
    protected ?ChatMemberUpdate $chatMember = null;

    /**
     * @var Collection<string, string>
     */
    protected Collection $data;

    protected Keyboard $originalKeyboard;

    public function __construct()
    {
        $this->originalKeyboard = Keyboard::make();
    }

    public function handle(Request $request, TelegraphBot $bot): void
    {
        $this->chat = null;
        $this->messageId = 0;
        $this->callbackQueryId = null;
        $this->message = null;
        $this->callbackQuery = null;
        $this->myChatMember = null;
        $this->chatMember = null;
        $this->data = Collection::empty();
        $this->originalKeyboard = Keyboard::make();

        try {
            $this->bot = $bot;
            $this->request = $request;

            if (config('telegraph.debug_mode', config('telegraph.webhook.debug'))) {
                Log::debug('Telegraph webhook received', $this->request->all());
            }

            // setup data
            $this->message = match (true) {
                $this->request->has('message') => Message::fromArray($this->request->input('message')),
                $this->request->has('edited_message') => Message::fromArray($this->request->input('edited_message')),
                $this->request->has('channel_post') => Message::fromArray($this->request->input('channel_post')),
                $this->request->has('edited_channel_post') => Message::fromArray($this->request->input('edited_channel_post')),
                default => null,
            };

            $this->callbackQuery = match (true) {
                $this->request->has('callback_query') => CallbackQuery::fromArray($this->request->input('callback_query')),
                default => null,
            };

            $this->myChatMember = match (true) {
                $this->request->has('my_chat_member') => ChatMemberUpdate::fromArray($this->request->input('my_chat_member')),
                default => null,
            };

            $this->chatMember = match (true) {
                $this->request->has('chat_member') => ChatMemberUpdate::fromArray($this->request->input('chat_member')),
                default => null,
            };

            if ($this->message === null && $this->callbackQuery === null && $this->myChatMember === null && $this->chatMember === null) {
                return;
            }

            // setup chat
            $this->setupChat();

            // run handlers
            match (true) {
                isset($this->message) && $this->message->migrateToChatId() => $this->handleMigrateToChat(),
                isset($this->message) => $this->handleMessage(),
                isset($this->callbackQuery) => $this->handleCallbackQuery(),
                isset($this->myChatMember) => $this->handleBotChatStatusUpdate($this->myChatMember),
                isset($this->chatMember) => $this->handleChatMemberUpdate($this->chatMember),
                default => null,
            };
        } catch (Throwable $throwable) {
            $this->onFailure($throwable);
        }
    }

    protected function onFailure(Throwable $throwable): void
    {
        if ($throwable instanceof NotFoundHttpException) {
            throw $throwable;
        }

        report($throwable);

        rescue(fn () => $this->reply(__('telegraph::errors.webhook_error_occurred')), report: false);
    }

    //---- Chat Setup
    protected function setupChat(): void
    {
        $telegramChat = $this->extractTelegramChat();

        $this->chat = $this->bot->chats()->firstOrNew([
            'chat_id' => $telegramChat->id(),
        ]);

        if (!$this->chat->exists) {
            if (!$this->allowUnknownChat()) {
                throw new NotFoundHttpException();
            }

            if (config('telegraph.security.store_unknown_chats_in_db', false)) {
                $this->createChat($telegramChat, $this->chat);
            }
        }
    }

    protected function extractTelegramChat(): Chat
    {
        $telegramChat = match (true) {
            isset($this->message) => $this->message->chat(),
            isset($this->callbackQuery) => $this->callbackQuery->message()?->chat(),
            isset($this->myChatMember) => $this->myChatMember->chat(),
            isset($this->chatMember) => $this->chatMember->chat(),
            default => null,
        };

        assert($telegramChat !== null);

        return $telegramChat;
    }

    protected function allowUnknownChat(): bool
    {
        return (bool) match (true) {
            isset($this->message) => config('telegraph.security.allow_messages_from_unknown_chats', false),
            isset($this->callbackQuery) => config('telegraph.security.allow_callback_queries_from_unknown_chats', false),
            isset($this->chatMember), isset($this->myChatMember) => true,
            default => false,
        };
    }

    protected function createChat(Chat $telegramChat, TelegraphChat $chat): void
    {
        $chat->name = $this->getChatName($telegramChat);
        $chat->save();
    }

    protected function getChatName(Chat $chat): string
    {
        return Str::of("")
            ->append("[", $chat->type(), ']')
            ->append(" ", $chat->title() ?? $chat->username() ?? '');
    }

    //---- Message Handlers
    protected function handleMessage(): void
    {
        $this->extractMessageData();

        $text = Str::of($this->message?->text() ?? '');

        if ($this->isCommand($text)) {
            $this->handleCommand($text);

            return;
        }

        if ($this->message?->newChatMembers()->isNotEmpty()) {
            foreach ($this->message->newChatMembers() as $member) {
                $this->handleChatMemberJoined($member);
            }

            return;
        }

        if ($this->message?->leftChatMember() !== null) {
            $this->handleChatMemberLeft($this->message->leftChatMember());

            return;
        }

        $this->handleChatMessage($text);
    }

    protected function extractMessageData(): void
    {
        assert($this->message !== null);

        $this->messageId = $this->message->id();

        $this->data = collect([
            'text' => $this->message->text(),
        ]);
    }

    protected function isCommand(Stringable $text): bool
    {
        $commandPrefixes = $this->commandPrefixes();

        $firstLetters = $commandPrefixes->map->substr(0, 1);

        $foundPrefix = $commandPrefixes->first(function (Stringable $prefix) use ($commandPrefixes, $firstLetters, $text) {
            if (!$text->startsWith($prefix)) {
                return false;
            }

            $cut = $text->substr(
                Str::length($prefix)
            )->before(' ');

            if ($cut->startsWith($commandPrefixes) || $cut->startsWith($firstLetters)) {
                return false;
            }

            return true;
        });

        return $foundPrefix !== null;
    }

    /**
     * @return Collection<int, Stringable>
     */
    protected function commandPrefixes(): Collection
    {
        /** @var string[] $prefixes */
        $prefixes = config('telegraph.commands.start_with', []);

        return collect($prefixes)
            ->push('/')
            ->map(fn (string $prefix) => str($prefix)->trim())
            ->unique()
            ->values();
    }

    protected function handleCommand(Stringable $text): void
    {
        [$command, $parameter] = $this->parseCommand($text);

        if (!$this->canHandle($command)) {
            $this->handleUnknownCommand($text);

            return;
        }

        $this->$command($parameter);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function parseCommand(Stringable $text): array
    {
        $command = $text->before('@')->before(' ');

        foreach ($this->commandPrefixes() as $prefix) {
            if ($command->startsWith($prefix)) {
                $parameter = $text->after($command)->after('@')->after(' ');
                $command = $command->after($prefix);

                break;
            }
        }

        return [(string) $command, (string) ($parameter ?? '')];
    }

    protected function canHandle(string $action): bool
    {
        if (!in_array($action, $this->allowedActions, true)
            || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $action)
            || $action === 'handle') {
            return false;
        }

        if (!method_exists($this, $action)) {
            return false;
        }

        $reflector = new ReflectionMethod($this::class, $action);
        if (!$reflector->isPublic() || $reflector->isStatic() || $reflector->isConstructor() || $reflector->isDestructor()) {
            return false;
        }

        return true;
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        if ($this->message?->chat()?->type() === Chat::TYPE_PRIVATE) {
            if (config('telegraph.report_unknown_webhook_commands', config('telegraph.webhook.report_unknown_commands', true))) {
                report(TelegramWebhookException::invalidCommand($this->parseCommand($text)[0]));
            }

            $this->chat?->html(__('telegraph::errors.invalid_command'))->send();
        }
    }

    //---- CallbackQuery Handlers
    protected function handleCallbackQuery(): void
    {
        $this->extractCallbackQueryData();

        /** @var string $action */
        $action = $this->callbackQuery?->data()->get('action') ?? '';

        if (!$this->canHandle($action)) {
            report(TelegramWebhookException::invalidAction($action));
            $this->reply(__('telegraph::errors.invalid_action'));

            return;
        }

        /** @phpstan-ignore-next-line */
        App::call([$this, $action], $this->data->toArray());
    }

    protected function extractCallbackQueryData(): void
    {
        assert($this->callbackQuery !== null);

        $this->messageId = $this->callbackQuery->message()?->id() ?? throw TelegramWebhookException::invalidData('message id missing');

        $this->callbackQueryId = $this->callbackQuery->id();

        /** @phpstan-ignore-next-line */
        $this->originalKeyboard = $this->callbackQuery->message()?->keyboard() ?? Keyboard::make();

        $this->data = $this->callbackQuery->data();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        // .. do nothing
    }

    protected function handleChatMemberJoined(User $member): void
    {
        // .. do nothing
    }

    protected function handleChatMemberLeft(User $member): void
    {
        // .. do nothing
    }

    protected function handleChatMemberUpdate(ChatMemberUpdate $chatMemberUpdate): void
    {
        // .. do nothing
    }

    protected function handleBotChatStatusUpdate(ChatMemberUpdate $chatMemberUpdate): void
    {
        // .. do nothing
    }

    protected function handleMigrateToChat(): void
    {
        $chat = $this->chat ?? throw TelegramWebhookException::invalidData('chat missing');
        $message = $this->message ?? throw TelegramWebhookException::invalidData('message missing');
        $chat->chat_id = $message->migrateToChatId() ?? throw TelegramWebhookException::invalidData('migration chat id missing');
        $chat->save();
    }

    //---- Helpers
    protected function replaceKeyboard(Keyboard $newKeyboard): void
    {
        $this->chat?->replaceKeyboard($this->messageId, $newKeyboard)->send();
    }

    protected function deleteKeyboard(): void
    {
        $this->chat?->deleteKeyboard($this->messageId)->send();
    }

    protected function reply(string $message, bool $showAlert = false): void
    {
        if (isset($this->callbackQueryId)) {
            $this->bot->replyWebhook($this->callbackQueryId, $message, $showAlert)->send();

            return;
        }

        $this->chat?->message($message)->send();
    }

    public function chatid(): void
    {
        $this->chat?->html("Chat ID: {$this->chat->chat_id}")->send();
    }
}
