<?php

namespace DefStudio\Telegraph;

use DefStudio\Telegraph\Commands\CreateNewBotCommand;
use DefStudio\Telegraph\Commands\CreateNewChatCommand;
use DefStudio\Telegraph\Commands\GetTelegramWebhookDebugInfoCommand;
use DefStudio\Telegraph\Commands\SetTelegramWebhookCommand;
use DefStudio\Telegraph\Commands\UnsetTelegramWebhookCommand;
use Illuminate\Support\ServiceProvider;

class TelegraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/telegraph.php', 'telegraph');
        $this->app->bind('telegraph', fn () => new Telegraph());
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'telegraph');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateNewBotCommand::class,
                CreateNewChatCommand::class,
                SetTelegramWebhookCommand::class,
                UnsetTelegramWebhookCommand::class,
                GetTelegramWebhookDebugInfoCommand::class,
            ]);
            $this->publishes([__DIR__.'/../config/telegraph.php' => config_path('telegraph.php')], 'telegraph-config');
            $this->publishesMigrations([
                __DIR__.'/../database/migrations/create_telegraph_bots_table.php' => database_path('migrations/create_telegraph_bots_table.php'),
                __DIR__.'/../database/migrations/create_telegraph_chats_table.php' => database_path('migrations/create_telegraph_chats_table.php'),
            ], 'telegraph-migrations');
        }
    }
}
