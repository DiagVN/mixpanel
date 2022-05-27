<?php

namespace MixPanel\Providers;

use Illuminate\Support\ServiceProvider;

class MixPanelProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__FILE__) . '/../Config/mixpanel.php' => config_path('mixpanel.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
