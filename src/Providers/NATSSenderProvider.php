<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Providers;

use Akbarali\NatsSender\Console\NatsChannelSender;
use Illuminate\Support\ServiceProvider;

class NATSSenderProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->registerCommands();
		//$this->offerPublishing();
	}
	
	protected function registerCommands(): void
	{
		if ($this->app->runningInConsole()) {
			$this->commands([
				NatsChannelSender::class,
			]);
		}
	}
	
	protected function offerPublishing(): void
	{
		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../../lang/eng/exceptions.php' => lang_path('eng/exceptions.php'),
			], 'nats-lang');
			
			$this->publishes([
				__DIR__.'/../../routes/nats.php' => base_path('routes/nats.php'),
			], 'nats-route');
		}
	}
	
	public function register(): void
	{
		if (!defined('NATS_SENDER_PATH')) {
			define('NATS_SENDER_PATH', dirname(__DIR__).'/');
		}
		
		$this->mergeConfigFrom(
			path: __DIR__.'/../../config/nats.php',
			key : 'nats'
		);
	}
	
}
