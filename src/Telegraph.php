<?php

/** @noinspection PhpUnused */
/** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace DefStudio\Telegraph;

use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Concerns\CallTraitsMethods;
use DefStudio\Telegraph\Concerns\ComposesMessages;
use DefStudio\Telegraph\Concerns\HasBotsAndChats;
use DefStudio\Telegraph\Concerns\InteractsWithCommands;
use DefStudio\Telegraph\Concerns\InteractsWithTelegram;
use DefStudio\Telegraph\Concerns\InteractsWithWebhooks;
use DefStudio\Telegraph\Concerns\ManagesKeyboards;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;

class Telegraph
{
    use CallTraitsMethods;
    use InteractsWithTelegram;
    use HasBotsAndChats;
    use ComposesMessages;
    use ManagesKeyboards;
    use InteractsWithWebhooks;
    use InteractsWithCommands;
    use Conditionable;

    public const PARSE_HTML = 'html';
    public const PARSE_MARKDOWN = 'markdown';
    public const PARSE_MARKDOWNV2 = 'MarkdownV2';

    protected const TELEGRAM_API_BASE_URL = 'https://api.telegram.org/bot';
    protected const TELEGRAM_API_FILE_BASE_URL = 'https://api.telegram.org/file/bot';

    public const ENDPOINT_GET_BOT_INFO = 'getMe';
    public const ENDPOINT_REGISTER_BOT_COMMANDS = 'setMyCommands';
    public const ENDPOINT_GET_REGISTERED_BOT_COMMANDS = 'getMyCommands';
    public const ENDPOINT_UNREGISTER_BOT_COMMANDS = 'deleteMyCommands';
    public const ENDPOINT_SET_WEBHOOK = 'setWebhook';
    public const ENDPOINT_UNSET_WEBHOOK = 'deleteWebhook';
    public const ENDPOINT_GET_WEBHOOK_DEBUG_INFO = 'getWebhookInfo';
    public const ENDPOINT_ANSWER_WEBHOOK = 'answerCallbackQuery';

    public const ENDPOINT_REPLACE_KEYBOARD = 'editMessageReplyMarkup';
    public const ENDPOINT_MESSAGE = 'sendMessage';
    public const ENDPOINT_DELETE_MESSAGE = 'deleteMessage';
    public const ENDPOINT_DELETE_MESSAGES = 'deleteMessages';
    public const ENDPOINT_PIN_MESSAGE = 'pinChatMessage';
    public const ENDPOINT_UNPIN_MESSAGE = 'unpinChatMessage';
    public const ENDPOINT_UNPIN_ALL_MESSAGES = 'unpinAllChatMessages';
    public const ENDPOINT_EDIT_MESSAGE = 'editMessageText';
    public const ENDPOINT_GET_CHAT_INFO = 'getChat';
    public const ENDPOINT_FORWARD_MESSAGE = 'forwardMessage';
    public const ENDPOINT_COPY_MESSAGE = 'copyMessage';
    public const ENDPOINT_SET_CHAT_MENU_BUTTON = 'setChatMenuButton';
    public const ENDPOINT_GET_CHAT_MENU_BUTTON = 'getChatMenuButton';




    /** @var array<string, mixed> */
    protected array $data = [];

    /** @var Collection<string, mixed> */
    protected Collection $files;

    public function __construct()
    {
        $this->files = Collection::empty();
    }

    public function send(): TelegraphResponse
    {
        $response = $this->sendRequestToTelegram();

        return TelegraphResponse::fromResponse($response);
    }

    /**
     * @return never-returns
     */
    public function dd(): void
    {
        dd($this->toArray());
    }

    public function dump(): Telegraph
    {
        dump($this->toArray());

        return $this;
    }

    public function withEndpoint(string $endpoint): static
    {
        $telegraph = clone $this;

        $telegraph->endpoint = ltrim($endpoint, '/');

        return $telegraph;
    }

    public function withData(string $key, mixed $value): static
    {
        $telegraph = clone $this;

        //@phpstan-ignore-next-line
        data_set($telegraph->data, $key, $value);

        return $telegraph;
    }

    public function inThread(int $thread_id): static
    {
        $telegraph = clone $this;

        //@phpstan-ignore-next-line
        data_set($telegraph->data, 'message_thread_id', $thread_id);

        return $telegraph;
    }
}
