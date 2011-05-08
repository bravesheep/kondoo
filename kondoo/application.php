<?php

namespace Kondoo;
abstract class Application {
	/**
	 * Implementations can overwrite the setup function for basic setup of their application. 
	 */
	abstract function setup();
	
	public $router;
	
	public static function run() {
		$script = $_SERVER['SCRIPT_FILENAME'];
		Config::set('app.location', dirname(dirname($script)) . DIRECTORY_SEPARATOR .
					'application');
		Config::set('app.public', dirname($script));
		
		$app = new static();
		$app->setup();
		
		$app->router = new Router;
	}
	
	protected function setRoutes(array $routes)
	{
		
	}
}