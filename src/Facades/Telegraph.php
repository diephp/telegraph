<?php

namespace DefStudio\Telegraph\Facades;

use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\ScopedPayloads\SetChatMenuButtonPayload;
use DefStudio\Telegraph\Support\Testing\Fakes\TelegraphFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getUrl()
 * @method static string getSafeApiUrl()
 * @method static \DefStudio\Telegraph\Telegraph  bot(TelegraphBot|string $bot)
 * @method static \DefStudio\Telegraph\Telegraph  chat(TelegraphChat|string $chat)
 * @method static \DefStudio\Telegraph\Telegraph  message(string $message)
 * @method static \DefStudio\Telegraph\Telegraph  withEndpoint(string $endpoint)
 * @method static \DefStudio\Telegraph\Telegraph  withData(string $key, mixed $value)
 * @method static \DefStudio\Telegraph\Telegraph  inThread(int $thread_id)
 * @method static \DefStudio\Telegraph\Telegraph  html(string $message)
 * @method static \DefStudio\Telegraph\Telegraph  reply(int $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  edit(string $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  markdown(string $message)
 * @method static \DefStudio\Telegraph\Telegraph  markdownV2(string $message)
 * @method static \DefStudio\Telegraph\Telegraph  registerWebhook()
 * @method static \DefStudio\Telegraph\Telegraph  unregisterWebhook(bool $dropPendingUpdates = false)
 * @method static \DefStudio\Telegraph\Telegraph  registerBotCommands(array<string, string> $commands)
 * @method static \DefStudio\Telegraph\Telegraph  getRegisteredCommands()
 * @method static \DefStudio\Telegraph\Telegraph  unregisterBotCommands()
 * @method static \DefStudio\Telegraph\Telegraph  getWebhookDebugInfo()
 * @method static \DefStudio\Telegraph\Telegraph  replyWebhook(string $callbackQueryId, string $message)
 * @method static \DefStudio\Telegraph\Telegraph  replaceKeyboard(string $messageId, Keyboard|callable $newKeyboard)
 * @method static \DefStudio\Telegraph\Telegraph  deleteKeyboard(string $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  deleteMessage(string $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  forwardMessage($fromChat, $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  pinMessage(string $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  unpinMessage(string $messageId)
 * @method static \DefStudio\Telegraph\Telegraph  unpinAllMessages()
 * @method static \DefStudio\Telegraph\Telegraph  botInfo()
 * @method static \DefStudio\Telegraph\Telegraph  setBaseUrl(string|null $url)
 * @method static \DefStudio\Telegraph\Telegraph  setHttpProxy(string|null $proxy)
 * @method static \DefStudio\Telegraph\Telegraph  chatInfo()
 * @method static \DefStudio\Telegraph\Telegraph  userProfilePhotos(string $userId)
 * @method static \DefStudio\Telegraph\Telegraph  chatMenuButton()
 * @method static SetChatMenuButtonPayload  setChatMenuButton()
 * @method static void  dumpSentData()
 * @method static void  assertSentData(string $endpoint, mixed[] $data = null, bool $exact = true)
 * @method static void  assertSent(string $message, bool $exact = true)
 * @method static void  assertNotSent(string $message, bool $exact = true)
 * @method static void  assertNothingSent()
 * @method static void  assertRegisteredWebhook(mixed[] $data = null, bool $exact = true)
 * @method static void  assertUnregisteredWebhook(mixed[] $data = null, bool $exact = true)
 * @method static void  assertRequestedWebhookDebugInfo(mixed[] $data = null, bool $exact = true)
 * @method static void  assertRepliedWebhook(string $message)
 * @method static void  assertRepliedWebhookIsAlert()
 *
 * @see \DefStudio\Telegraph\Telegraph
 */
class Telegraph extends Facade
{
    protected static $cached = false;

    /**
     * @param array<string, array<mixed>> $replies
     */
    public static function fake(array $replies = []): TelegraphFake
    {
        TelegraphFake::reset();
        static::swap($fake = new TelegraphFake($replies));

        return $fake;
    }

    public static function getFacadeRoot()
    {
        $instance = parent::getFacadeRoot();
        if ($instance instanceof TelegraphFake) {
            $instance->prepareForNewRequest();
        }

        return $instance;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'telegraph';
    }
}
