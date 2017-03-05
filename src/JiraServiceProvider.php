<?php

namespace Univerze\Jira;

use Illuminate\Support\ServiceProvider;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Jira::class);
    }
}
