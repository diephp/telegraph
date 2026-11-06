# Telegraph, Simplified

**Next release: 2.0.0** | Composer: `diephp/telegraph` | PHP 8.3-8.5 | Laravel 12/13

An independent, simplified fork of [defstudio/telegraph](https://github.com/defstudio/telegraph), maintained at [diephp/telegraph](https://github.com/diephp/telegraph). Built for Restsify order notifications and interactive bot conversations.

The PHP namespace remains `DefStudio\Telegraph`. The Composer package name is different, and the removed upstream features are intentionally unsupported.

## Differences From the Original

| Area | This fork |
| --- | --- |
| Scope | Text notifications, inline buttons, commands and callback conversations |
| Message builder | Compose first; select bot/chat later, immediately before sending |
| Reuse | Send the same prepared text message to independent recipients through different bots |
| Destination lookup | Implicit chat selection is limited to the selected bot |
| Webhook authentication | A configured secret is required; missing configuration fails closed |
| Action dispatch | Only methods listed in the handler's $allowedActions are remotely callable |
| Worker reuse | Request/callback context is reset between updates |
| Dependencies | Laravel-native provider; no laravel-package-tools dependency |
| Removed APIs | Payments, games, polls, media, chat administration and other unused modules |

This changes the lifecycle of the existing fluent builder; it is not a complete rewrite of Telegraph. The original `html()`, `keyboard()`, `bot()`, `chat()` and `send()` API remains familiar.

## Requirements

| Framework | PHP |
| --- | --- |
| Laravel 12.69+ | 8.3, 8.4, 8.5 |
| Laravel 13.30+ | 8.3, 8.4, 8.5 |

Use current patched PHP builds. As of 2026-11-06, the October PHP security fixes require 8.3.36+, 8.4.27+ or 8.5.12+, or an equivalent distribution backport. Composer's PHP constraint specifies language compatibility and cannot verify distribution backports.

CI covers both Laravel branches on PHP 8.3, 8.4 and 8.5. See the workflow results for the status of each combination.

## Installation

After the first release is published on Packagist:

```bash
composer require diephp/telegraph:^2.0
php artisan vendor:publish --tag=telegraph-config
php artisan vendor:publish --tag=telegraph-migrations
php artisan migrate
```

Until publication, configure a Composer VCS repository for `https://github.com/diephp/telegraph.git` and require `diephp/telegraph:dev-main`.

Laravel discovers the provider automatically. Existing applications with custom models/migrations should retain their schema instead of publishing duplicate migrations.

## Compose Once, Send Later

A message-producing service does not need to know its recipients:

```php
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

$message = Telegraph::html('<b>Order #42</b>')
    ->keyboard(Keyboard::make()->row([
        Button::make('Accept')
            ->action('accept_order')
            ->param('order_id', 42),
        Button::make('View')->url('https://example.com/orders/42'),
    ]))
    ->withoutPreview();

$response = $message->bot($firstBot)->chat($firstChatId)->send();
$message->bot($secondBot)->chat($secondChatId)->send();

if ($response->telegraphError()) {
    // Handle Telegram's rejection.
}

$messageId = $response->telegraphMessageId();
```

Fluent Telegraph methods return clones. Assigning a recipient does not modify the prepared text builder. Missing bot/chat context is rejected before the HTTP request. A sole configured bot/chat may still be inferred at send time.

## Retained Functionality

- HTML, Markdown and MarkdownV2 text messages, silent/protected messages and preview control.
- Editing, replying, deleting, forwarding, copying and pinning text messages.
- Inline keyboards, parameterized callbacks, keyboard replacement/removal and force replies.
- Topic destinations through `inThread()`, without forum administration.
- Commands, text conversations, membership updates and group-to-supergroup migration.
- Bot/chat models, cache/file model storage and menu buttons.
- Webhook registration, removal and diagnostics through Artisan.
- Response inspection, custom trusted API servers/proxies and testing fakes.

Applications should queue their notification service and call `send()` inside that job when they need to save Telegram's message ID.

## Webhook Setup and Actions

Configure a secret containing 1-256 letters, digits, underscores or hyphens:

```dotenv
TELEGRAPH_WEBHOOK_SECRET=replace-with-a-long-random-secret
```

Register or re-register the webhook after changing it:

```bash
php artisan telegraph:set-webhook
# For multiple bots:
php artisan telegraph:set-webhook 2
```

Registration sends the secret to Telegram. The package controller checks the matching `X-Telegram-Bot-Api-Secret-Token` header. Missing configuration returns HTTP 503; a missing/wrong header returns HTTP 403. Custom controllers must perform the same authentication.

Explicitly expose actions in your handler:

```php
use DefStudio\Telegraph\Handlers\WebhookHandler;

class RestaurantWebhookHandler extends WebhookHandler
{
    protected array $allowedActions = ['start', 'accept_order'];

    public function start(string $parameter = ''): void
    {
        $this->chat->html('Welcome')->send();
    }

    public function accept_order(int $order_id): void
    {
        // Authorize this user/chat against the order before changing it.
        $this->reply('Order accepted');
    }
}
```

The allowlist controls routing, not business authorization. Treat all callback parameters as untrusted. Scope order/channel lookups by the current bot, chat and tenant.

## Removed Functionality

Payments/Stars/refunds, games, polls/quizzes, inline query handlers, media/file transfer, reply keyboard builders, chat administration/invites/permissions, forum management, reactions, Telegram Business, long polling and the built-in dispatch job are removed. Their specialized DTOs and test fixtures are removed too.

Inline query handlers and inline keyboard buttons are different features: inline buttons are supported. Unsupported top-level update types are ignored.

## Migration From defstudio/telegraph

After publication, remove the original dependency and require `diephp/telegraph:^2.0`. Both packages cannot be installed together because they share a namespace.

Keep existing PHP imports and model subclasses. Review the removed APIs, configure the webhook secret, and add `$allowedActions` to custom handlers before deploying.

Update any hard-coded `vendor/defstudio/telegraph` paths to `vendor/diephp/telegraph`. If the application disables auto-discovery, update its `extra.laravel.dont-discover` entry to the new package name and retain its custom provider registration.

## Security and Development

See [the security policy](.github/SECURITY.md). Use `getSafeApiUrl()` for logs; `getApiUrl()`, `getUrl()` and the URL in `toArray()` contain credentials. Keep payload debugging off in production.

```bash
composer install
composer test
composer audit
```

Development dependencies are not installed in applications that consume this package.

## Releases

Version 2.0.0 is prepared, not automatically published. Composer derives the version from a Git tag; no hard-coded `version` field is used in composer.json. See [RELEASING.md](RELEASING.md) for the Packagist setup, tag commands and consumer migration.

The remaining upstream `docs/` pages may describe a wider API. This README is the maintained functional reference.

## License and Attribution

Derived from Telegraph by Fabio Ivona, def:studio and the upstream contributors. The original [MIT license](LICENSE.md) is retained.
