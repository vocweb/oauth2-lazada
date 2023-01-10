<?php

namespace Vocweb\Oauth2Lazada;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewException;


class Oauth2LazadaServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->registerConfig();
	}

	public function boot()
	{
		if ($this->app->runningInConsole()) {
			$this->registerCommands();
		}
	}

	protected function registerConfig(): void
	{
		// $this->mergeConfigFrom(__DIR__ . '/../config/lazada.php', 'lazada');
	}

	protected function registerCommands(): void
	{
		// if ($this->app['config']->get('flare.key')) {
		//     $this->commands([
		//         TestCommand::class,
		//     ]);
		// }

		// if ($this->app['config']->get('ignition.register_commands')) {
		//     $this->commands([
		//         SolutionMakeCommand::class,
		//         SolutionProviderMakeCommand::class,
		//     ]);
		// }
	}


}
