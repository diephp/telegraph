# Telegraph for Restsify

A focused fork of [defstudio/telegraph](https://github.com/defstudio/telegraph), maintained at [diephp/telegraph](https://github.com/diephp/telegraph).

The Composer package name and PHP namespace remain `defstudio/telegraph` and `DefStudio\\Telegraph` so existing Restsify integrations continue to resolve them.

## Supported Features

- Reusable HTML/Markdown message builders with deferred bot and chat assignment.
- Sending, editing, replying, deleting, forwarding, copying and pinning text messages.
- Inline keyboards, callback actions with parameters, keyboard replacement and removal.
- Forum topic destinations through `inThread()`.
- Webhook commands, callbacks, bot membership changes and chat migration.
- Bot/chat models, model storage, webhook setup and diagnostic Artisan commands.
- HTTP response inspection and test fakes.

Payments, games, polls, inline query handlers, media/file transfers, reply keyboard builders, chat administration, forum management, Telegram Business, reactions, long polling and the built-in dispatch job have been removed. This fork is not a drop-in replacement for applications using those upstream features. Unsupported update types are ignored.

## Installation

Configure a Composer VCS repository pointing to `https://github.com/diephp/telegraph.git` and require the desired fork branch or tag as `defstudio/telegraph`.

For a new installation:

```bash
php artisan vendor:publish --tag=telegraph-config
php artisan vendor:publish --tag=telegraph-migrations
php artisan migrate
```

Existing applications with custom models and migrations can keep their own schema and configuration.

## Reusable Messages

```php
use DefStudio\\Telegraph\\Facades\\Telegraph;
use DefStudio\\Telegraph\\Keyboard\\Button;
use DefStudio\\Telegraph\\Keyboard\\Keyboard;

$message = Telegraph::html('<b>Order #42</b>')
    ->keyboard(Keyboard::make()->row([
        Button::make('Accept')->action('accept_order')->param('order_id', 42),
    ]))
    ->withoutPreview();

$message->bot($firstBot)->chat($firstChatId)->send();
$message->bot($secondBot)->chat($secondChatId)->send();
```

Use `send()` to inspect `telegraphError()` and obtain `telegraphMessageId()`. Applications can queue their notification service while keeping access to the Telegram response.

## Webhook Authentication

Set `TELEGRAPH_WEBHOOK_SECRET` and register the webhook again with `php artisan telegraph:set-webhook`. The same secret is sent during registration and checked against the incoming `X-Telegram-Bot-Api-Secret-Token` header. A missing or invalid header is rejected when a secret is configured.

Custom controllers that bypass the package controller must perform the same check. Keep application-specific authorization in callback handlers.

## Development

```bash
vendor/bin/pest --no-coverage
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

The remaining `docs/` pages were inherited from upstream and may describe a wider API. This README describes the maintained scope.

## License and Attribution

Derived from Telegraph by Fabio Ivona, def:studio and the upstream contributors. The original [MIT license](LICENSE.md) is retained.
