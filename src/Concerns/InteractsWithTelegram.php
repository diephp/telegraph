<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace DefStudio\Telegraph\Concerns;

use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Models\Concerns\HasCustomUrl;
use DefStudio\Telegraph\Telegraph;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @mixin Telegraph
 */
trait InteractsWithTelegram
{
    protected string $endpoint;

    protected string|null $baseUrl = null;

    protected string|null $httpProxy = null;

    protected function sendRequestToTelegram(): Response
    {
        $request = Http::asJson()->withoutRedirecting();

        // Apply proxy configuration if set
        if ($proxy = $this->getHttpProxy()) {
            $request->withOptions(['proxy' => $proxy]);
        }

        try {
            /** @phpstan-ignore-next-line */
            return $request->timeout(config('telegraph.http_timeout', 30))->connectTimeout(config('telegraph.http_connection_timeout', 10))->post($this->getApiUrl(), $this->prepareData());
        } catch (HttpClientException $exception) {
            throw new TelegraphException(str_replace($this->getBotToken(), '[redacted]', $exception->getMessage()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareData(): array
    {
        $data = $this->data;

        $data = $this->pipeTraits('preprocessData', $data);
        $data = $this->prepareChatData($data);

        //@phpstan-ignore-next-line
        return $data;
    }

    public function setBaseUrl(string|null $url): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->baseUrl = $url;

        return $telegraph;
    }

    protected function getBaseUrl(): string
    {
        /* @phpstan-ignore-next-line */
        return Str::of($this->baseUrl ?? config('telegraph.telegram_api_url', 'https://api.telegram.org/'))
            ->rtrim('/')
            ->append('/bot');
    }

    public function setHttpProxy(string|null $proxy): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->httpProxy = $proxy;

        return $telegraph;
    }

    protected function getHttpProxy(): string|null
    {
        /* @phpstan-ignore-next-line */
        return $this->httpProxy ?? config('telegraph.http_proxy');
    }

    public function getUrl(): string
    {
        $bot = $this->getBot();
        $data = $this->prepareData();

        $url = $bot instanceof HasCustomUrl
            ? $bot->getUrl()
            : Str::of($this->getBaseUrl())
                ->append($this->getBotToken())
                ->toString();

        /** @phpstan-ignore-next-line */
        return Str::of($url)
            ->append('/', $this->endpoint)
            ->when(!empty($data), fn (Stringable $str) => $str->append('?', http_build_query($data)))
            ->toString();
    }

    public function getApiUrl(): string
    {
        $bot = $this->getBot();

        $url = $bot instanceof HasCustomUrl
            ? $bot->getUrl()
            : Str::of($this->getBaseUrl())
                ->append($this->getBotToken())
                ->toString();

        /** @phpstan-ignore-next-line */
        return Str::of($url)
            ->append('/', $this->endpoint)
            ->toString();
    }

    public function getSafeApiUrl(): string
    {
        return str_replace($this->getBotToken(), '[redacted]', $this->getApiUrl());
    }

    /**
     * @return array{url:string, payload:array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->getApiUrl(),
            'payload' => $this->prepareData(),
            'files' => $this->files->toArray(),
        ];
    }
}
