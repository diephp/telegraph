<?php

/** @noinspection PhpUnused */
/** @noinspection PhpDocSignatureIsNotCompleteInspection */

namespace DefStudio\Telegraph\DTO;

use Carbon\CarbonInterface;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, string|int|bool|array<array-key, mixed>>
 */
class Message implements Arrayable
{
    private int $id;
    private ?int $messageThreadId = null;
    private CarbonInterface $date;
    private ?CarbonInterface $editDate = null;
    private string $text;
    private bool $protected = false;
    private ?User $from = null;
    private ?User $forwardedFrom = null;
    private ?Chat $chat = null;
    private Keyboard $keyboard;
    private ?Message $replyToMessage = null;
    /** @var Collection<array-key, User> */
    private Collection $newChatMembers;
    private ?User $leftChatMember = null;
    private ?string $migrateToChatId = null;
    /** @var Collection<array-key, Entity> */
    private Collection $entities;

    private function __construct()
    {
        $this->entities = Collection::empty();
    }

    /**
     * @param array{
     *     message_id: int,
     *     date: int,
     *     message_thread_id?: int,
     *     edit_date?: int,
     *     text?: string,
     *     caption?: string,
     *     has_protected_content?: bool,
     *     reply_to_message?: array<string, mixed>,
     *     from?: array<string, mixed>,
     *     forward_from?: array<string, mixed>,
     *     chat?: array<string, mixed>,
     *     reply_markup?: array{inline_keyboard?: array<array<array<string, mixed>>>},
     *     new_chat_members?: array<array<string, mixed>>,
     *     left_chat_member?: array<string, mixed>,
     *     entities?: array<array<string, mixed>>,
     *     migrate_to_chat_id?: int
     * } $data
     */
    public static function fromArray(array $data): Message
    {
        $message = new self();
        $message->id = $data['message_id'];
        if (isset($data['message_thread_id'])) {
            $message->messageThreadId = $data['message_thread_id'];
        }
        $message->date = Carbon::createFromTimestamp($data['date']);
        if (isset($data['edit_date'])) {
            $message->editDate = Carbon::createFromTimestamp($data['edit_date']);
        }
        $message->text = $data['text'] ?? $data['caption'] ?? '';
        $message->protected = $data['has_protected_content'] ?? false;
        if (isset($data['reply_to_message'])) {
            $message->replyToMessage = Message::fromArray($data['reply_to_message']);
        }
        if (isset($data['from'])) {
            $message->from = User::fromArray($data['from']);
        }
        if (isset($data['forward_from'])) {
            $message->forwardedFrom = User::fromArray($data['forward_from']);
        }
        if (isset($data['chat'])) {
            $message->chat = Chat::fromArray($data['chat']);
        }
        if (isset($data['reply_markup']) && isset($data['reply_markup']['inline_keyboard'])) {
            $message->keyboard = Keyboard::fromArray($data['reply_markup']['inline_keyboard']);
        } else {
            $message->keyboard = Keyboard::make();
        }
        /* @phpstan-ignore-next-line */
        $message->newChatMembers = collect($data['new_chat_members'] ?? [])->map(fn (array $userData) => User::fromArray($userData));
        if (isset($data['left_chat_member'])) {
            $message->leftChatMember = User::fromArray($data['left_chat_member']);
        }
        if (isset($data['entities']) && $data['entities']) {
            /* @phpstan-ignore-next-line */
            $message->entities = collect($data['entities'])->map(fn (array $entity) => Entity::fromArray($entity));
        }
        if (isset($data['migrate_to_chat_id'])) {
            $message->migrateToChatId = (string) $data['migrate_to_chat_id'];
        }

        return $message;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function messageThreadId(): ?int
    {
        return $this->messageThreadId;
    }

    public function date(): CarbonInterface
    {
        return $this->date;
    }

    public function editDate(): ?CarbonInterface
    {
        return $this->editDate;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function protected(): bool
    {
        return $this->protected;
    }

    public function from(): ?User
    {
        return $this->from;
    }

    public function forwardFrom(): ?User
    {
        return $this->forwardedFrom;
    }

    public function chat(): ?Chat
    {
        return $this->chat;
    }

    public function keyboard(): Keyboard
    {
        return $this->keyboard;
    }

    public function replyToMessage(): ?Message
    {
        return $this->replyToMessage;
    }

    /**
     * @return Collection<array-key, User>
     */
    public function newChatMembers(): Collection
    {
        return $this->newChatMembers;
    }

    public function leftChatMember(): ?User
    {
        return $this->leftChatMember;
    }

    public function migrateToChatId(): ?string
    {
        return $this->migrateToChatId;
    }

    /**
     * @return Collection<array-key, Entity>
     */
    public function entities(): Collection
    {
        return $this->entities;
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'message_thread_id' => $this->messageThreadId,
            'date' => $this->date->toISOString(),
            'edit_date' => $this->editDate?->toISOString(),
            'text' => $this->text,
            'protected' => $this->protected,
            'from' => $this->from?->toArray(),
            'forwarded_from' => $this->forwardedFrom?->toArray(),
            'chat' => $this->chat?->toArray(),
            'keyboard' => $this->keyboard->isFilled() ? $this->keyboard->toArray() : null,
            'reply_to_message' => $this->replyToMessage?->toArray(),
            'new_chat_members' => $this->newChatMembers->toArray(),
            'left_chat_member' => $this->leftChatMember?->toArray(),
            'migrate_to_chat_id' => (int) $this->migrateToChatId,
            'entities' => $this->entities->toArray(),
        ], fn ($value) => $value !== null);
    }
}
