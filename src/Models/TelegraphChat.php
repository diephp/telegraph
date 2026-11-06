<?php

/** @noinspection PhpUnused */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace DefStudio\Telegraph\Models;

use DefStudio\Telegraph\Concerns\HasStorage;
use DefStudio\Telegraph\Contracts\Storable;
use DefStudio\Telegraph\Database\Factories\TelegraphChatFactory;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph as TelegraphFacade;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\ScopedPayloads\SetChatMenuButtonPayload;
use DefStudio\Telegraph\Telegraph;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DefStudio\Telegraph\Models\TelegraphChat
 *
 * @property int $id
 * @property string $chat_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TelegraphBot $bot
 */
class TelegraphChat extends Model implements Storable
{
    /** @use HasFactory<TelegraphChatFactory> */
    use HasFactory;
    use HasStorage;

    protected $fillable = [
        'chat_id',
        'name',
    ];

    /**
     * @return TelegraphChatFactory
     */
    protected static function newFactory(): Factory
    {
        return TelegraphChatFactory::new();
    }

    public static function booted(): void
    {
        self::created(function (TelegraphChat $chat) {
            if (empty($chat->name)) {
                $chat->name = "Chat #$chat->id";
                $chat->saveQuietly();
            }
        });
    }

    public function storageKey(): string|int
    {
        return $this->id;
    }

    /**
     * @return BelongsTo<TelegraphBot, TelegraphChat>
     */
    public function bot(): BelongsTo
    {
        /** @phpstan-ignore-next-line */
        return $this->belongsTo(config('telegraph.models.bot'), 'telegraph_bot_id');
    }

    /**
     * @return array{id: int, type: string, title?: string, description?: string, username?: string, first_name?: string, last_name?: string, photo?: array<string, mixed>, pinned_message?: array<string, mixed>, permissions?: array<string, mixed>, bio?: string, has_private_forwards?: true, has_restricted_voice_and_video_messages?: true, join_to_send_messages?: true, join_by_request?: true, has_protected_content?: true, invite_link?: string, sticker_set_name?: string, sticker_set_name?: true, linked_chat_id?: int, slow_mode_delay?: int, location?: array<string, mixed>, message_auto_delete_time?: int}
     */
    public function info(): array
    {
        $reply = TelegraphFacade::chat($this)->chatInfo()->send();

        if ($reply->telegraphError()) {
            throw TelegraphException::failedToRetrieveChatInfo();
        }

        /* @phpstan-ignore-next-line */
        return $reply->json('result');
    }

    public function withEndpoint(string $endpoint): Telegraph
    {
        return TelegraphFacade::chat($this)->withEndpoint($endpoint);
    }

    public function withData(string $key, mixed $value): Telegraph
    {
        return TelegraphFacade::chat($this)->withData($key, $value);
    }

    public function inThread(int $thread_id): Telegraph
    {
        return TelegraphFacade::chat($this)->inThread($thread_id);
    }

    public function message(string $message): Telegraph
    {
        return TelegraphFacade::chat($this)->message($message);
    }

    public function html(string $message): Telegraph
    {
        return TelegraphFacade::chat($this)->html($message);
    }

    public function markdown(string $message): Telegraph
    {
        return TelegraphFacade::chat($this)->markdown($message);
    }

    public function markdownV2(string $message): Telegraph
    {
        return TelegraphFacade::chat($this)->markdownV2($message);
    }

    public function reply(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->reply($messageId);
    }

    /**
     * @param  Keyboard|callable(Keyboard):Keyboard  $newKeyboard
     */
    public function replaceKeyboard(int $messageId, Keyboard|callable $newKeyboard): Telegraph
    {
        return TelegraphFacade::chat($this)->replaceKeyboard($messageId, $newKeyboard);
    }

    public function deleteKeyboard(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->deleteKeyboard($messageId);
    }

    public function edit(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->edit($messageId);
    }

    public function deleteMessage(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->deleteMessage($messageId);
    }

    /**
     * @param  array<int>  $messageIds
     */
    public function deleteMessages(array $messageIds): Telegraph
    {
        return TelegraphFacade::chat($this)->deleteMessages($messageIds);
    }

    public function pinMessage(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->pinMessage($messageId);
    }

    public function unpinMessage(int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->unpinMessage($messageId);
    }

    public function unpinAllMessages(): Telegraph
    {
        return TelegraphFacade::chat($this)->unpinAllMessages();
    }

    public function setBaseUrl(string|null $url): Telegraph
    {
        return TelegraphFacade::chat($this)->setBaseUrl($url);
    }

    public function setHttpProxy(string|null $proxy): Telegraph
    {
        return TelegraphFacade::chat($this)->setHttpProxy($proxy);
    }

    public function forwardMessage(TelegraphChat|int $fromChat, int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->forwardMessage($fromChat, $messageId);
    }

    public function menuButton(): Telegraph
    {
        return TelegraphFacade::chat($this)->chatMenuButton();
    }

    public function setMenuButton(): SetChatMenuButtonPayload
    {
        return TelegraphFacade::chat($this)->setChatMenuButton();
    }

    public function copyMessage(TelegraphChat|int $fromChat, int $messageId): Telegraph
    {
        return TelegraphFacade::chat($this)->copyMessage($fromChat, $messageId);
    }
}
