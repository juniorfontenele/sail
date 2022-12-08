<?php

namespace JuniorFontenele\Sail;

use Illuminate\Support\ServiceProvider;
use JuniorFontenele\Sail\Console\InstallCommand;

class SailServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
          ]);
        }
    }

    public function provides()
    {
        // return [
        //     InstallCommand::class,
        // ];
    }
}
