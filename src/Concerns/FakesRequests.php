<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

namespace DefStudio\Telegraph\Concerns;

use DefStudio\Telegraph\Telegraph;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

//@phpstan-ignore-next-line
trait FakesRequests
{
    /** @var array<int, mixed[]> */
    protected static array $sentMessages = [];


    /** @var array<string, mixed[]> */
    protected array $replies = [];

    public static function reset(): void
    {
        self::$sentMessages = [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function messageToArray(): array
    {
        return [
            'url' => $this->getApiUrl(),
            'endpoint' => $this->endpoint ?? null,
            'data' => $this->prepareData(),
            'files' => $this->files,
            'bot_token' => $this->getBotToken(),
            'chat_id' => $this->getChatIfAvailable()->id ?? null,
            'message' => $this->data['text'] ?? null,
            'parse_mode' => $this->data['parse_mode'] ?? null,
        ];
    }

    protected function sendRequestToTelegram(): Response
    {
        $message = $this->messageToArray();
        self::$sentMessages[] = $message;

        $response = $this->replies[$this->endpoint] ?? match ($this->endpoint) {
            Telegraph::ENDPOINT_MESSAGE, Telegraph::ENDPOINT_EDIT_MESSAGE => [
                'ok' => true,
                'result' => [
                    'message_id' => $this->data['message_id'] ?? rand(1, 99999),
                    'chat' => ['id' => $message['data']['chat_id'], 'title' => 'Test Chat', 'type' => 'channel'],
                    'date' => now()->timestamp,
                    'text' => $this->data['text'],
                ],
            ],
            Telegraph::ENDPOINT_GET_BOT_INFO => [
                'ok' => true,
                'result' => ['id' => 42, 'is_bot' => true, 'first_name' => 'telegraph-test', 'username' => 'test_bot', 'can_join_groups' => true, 'can_read_all_group_messages' => false, 'supports_inline_queries' => false],
            ],
            Telegraph::ENDPOINT_GET_CHAT_INFO => [
                'ok' => true,
                'result' => ['id' => 42, 'type' => 'group', 'title' => 'foo', 'description' => 'bar', 'has_private_forwards' => true, 'join_by_request' => true, 'has_protected_content' => true],
            ],
            default => ['ok' => true],
        };

        return new Response(new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($response, JSON_THROW_ON_ERROR)));
    }

    public function dumpSentData(): void
    {
        dump(self::$sentMessages);
    }

    /** @phpstan-ignore-next-line  */
    public static function assertSentData(string $endpoint, array $data = null, bool $exact = true): void
    {
        if ($data == null) {
            $errorMessage = sprintf("Failed to assert that a request was sent to [%s] endpoint (sent %d requests so far)", $endpoint, count(self::$sentMessages));
        } else {
            $errorMessage = sprintf("Failed to assert that a request was sent to [%s] endpoint with the given data (sent %d requests so far)", $endpoint, count(self::$sentMessages));
        }

        Assert::assertNotEmpty(static::searchMessages($endpoint, $data, $exact)->toArray(), $errorMessage);
    }

    /** @phpstan-ignore-next-line  */
    public static function assertNotSentData(string $endpoint, array $data = null, bool $exact = true): void
    {
        if ($data == null) {
            $errorMessage = sprintf("Failed to assert that a request was sent to [%s] endpoint (sent %d requests so far)", $endpoint, count(self::$sentMessages));
        } else {
            $errorMessage = sprintf("Failed to assert that a request was sent to [%s] endpoint with the given data (sent %d requests so far)", $endpoint, count(self::$sentMessages));
        }

        Assert::assertEmpty(static::searchMessages($endpoint, $data, $exact)->toArray(), $errorMessage);
    }

    /** @phpstan-ignore-next-line  */
    protected static function searchMessages(string $endpoint, array $data = null, bool $exact = true): Collection
    {
        return collect(self::$sentMessages)
            ->filter(fn (array $message): bool => $message['endpoint'] == $endpoint)
            ->filter(function (array $message) use ($data, $exact): bool {
                foreach ($data ?? [] as $key => $value) {
                    /** @var array<string, string> $data */
                    $messageData = $message['data'];
                    if (!Arr::has($messageData, $key)) {
                        return false;
                    }

                    if ($exact) {
                        if ($value != $messageData[$key]) {
                            return false;
                        }
                    } else {
                        if (!Str::of($messageData[$key])->contains($value)) {
                            return false;
                        }
                    }
                }

                return true;
            });
    }
}
