<?php

namespace Vendor\CustomCommands;

use Illuminate\Support\ServiceProvider;
use Vendor\CustomCommands\Commands\MakeServiceCommand;
use Vendor\CustomCommands\Commands\MakeRepositoryCommand;

class CustomCommandsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Register the Artisan commands
        $this->commands([
            MakeServiceCommand::class,
            MakeRepositoryCommand::class,
        ]);
    }
}
