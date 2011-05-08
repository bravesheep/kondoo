<?php

namespace Kondoo;
abstract class Application {
	/**
	 * Implementations can overwrite the setup function for basic setup of their application. 
	 */
	abstract function setup();
	
	/**
	 * The router that is used for routing and reverse routing of urls to controllers and actions.
	 * @var Kondoo\Router
	 */
	public $router;
	
	/**
	 * Request parameters, variables etc are stored in the request.
	 * @var Kondoo\Request
	 */
	public $request;
	
	/**
	 * Contains the latest instance of the application after the run function has dispatched
	 * the request to a dispatcher
	 * @var Kondoo\Application
	 */
	private static $application = null;
	
	/**
	 * Run the application, call this static method on the application that should be run.
	 */
	public static function run() {
		$script = $_SERVER['SCRIPT_FILENAME'];
		Config::set('app.location', dirname(dirname($script)) . DIRECTORY_SEPARATOR .
					'application');
		Config::set('app.public', dirname($script));
		
		Config::set('app.controllers', './controllers');
		Config::set('app.templates', './templates');
		
		$app = new static();
		$app->router = new Router();
		
		$app->setup();
		list($url) = explode('?', $_SERVER['REQUEST_URI']);
		$routed = $app->router->route($url);
		
		$app->request = new Request($app);
		$app->request->controllerName = lcfirst($routed['controller']);
		$app->request->actionName = $routed['action'];
		self::$application = $app;
		
		$dispatcher = new Dispatcher();
		$output = $dispatcher->dispatch($app, $app->request);
		print $output;
	}
	
	/**
	 * Get the latest application instance
	 * @return Kondoo\Application
	 */
	public static function get()
	{
		return self::$application;
	}
	
	/**
	 * Set the list of routes
	 * @param array $routes
	 */
	protected function setRoutes(array $routes)
	{
		$this->router->addRoutes($routes);
	}
}