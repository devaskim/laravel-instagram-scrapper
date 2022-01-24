<?php namespace InstagramScrapper;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class InstagramServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        // Register the config publish path
        $configPath = __DIR__ . '/../config/instagram-scrapper.php';
        $this->mergeConfigFrom($configPath, 'instagram-scrapper');
        $this->publishes([$configPath => config_path('instagram-scrapper.php')], 'config');
        
        $this->app->singleton('instagram-scrapper', function ($app) {
            $scrapper = $app->make('InstagramScrapper\Scrapper');
            return $scrapper;
        });

        $this->app->singleton('command.instagram.scrape', function ($app) {
            return new Console\ScrapeCommand($app['instagram-scrapper']);
        });
        $this->commands('command.instagram.scrape');
	}

    /**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $migrationPath = __DIR__.'/../database/migrations';
        $this->publishes([
            $migrationPath => base_path('database/migrations'),
        ], 'migrations');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('instagram-scrapper',
            'command.instagram.scrape'
        );
	}

}
