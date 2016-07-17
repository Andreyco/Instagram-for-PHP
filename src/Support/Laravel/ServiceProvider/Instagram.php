<?php namespace Andreyco\Instagram\Support\Laravel\ServiceProvider;

use Illuminate\Support\ServiceProvider;

class Instagram extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('instagram.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('andreyco.instagram', function($app) {
            return new \Andreyco\Instagram\Client([
               'apiKey'      => $app['config']->get('andreyco/instagram::clientId'),
               'apiSecret'   => $app['config']->get('andreyco/instagram::clientSecret'),
               'apiCallback' => $app['config']->get('andreyco/instagram::redirectUri'),
               'scope'       => $app['config']->get('andreyco/instagram::scope'),
           ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('andreyco.instagram');
    }

}
