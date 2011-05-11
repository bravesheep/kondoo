<?php

namespace Kondoo;

use Kondoo\Filter\IFilter;
use \Exception;

abstract class Application {
	
	const EVENT_FIRST   = 1;
	const EVENT_LAST    = 2;
	
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
	
	private $redirected = false;
	
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
					'application' . DIRECTORY_SEPARATOR);
		Config::set('app.public', dirname($script) . DIRECTORY_SEPARATOR);
		
		Config::set('app.controllers', './controllers/');
		Config::set('app.templates', './templates/');
		Config::set('output.late', true);
		
		$app = new static();
		self::$application = $app;
		
		$app->router = new Router();
		$app->setup();
		$app->request = new Request($app);
		
		$maxRedirects = (int) Config::get('app.max_redirects', 5);
		Config::set('app.redirect', 0);
		do {
			Output::reset();
			$app->redirected = false;
			Dispatcher::dispatch($app, $app->request);
			if($app->redirected) {
				Config::increment('app.redirect');
			}
		} while($app->redirected && Config::get('app.redirect') < $maxRedirects);
		
		if($app->redirected && Config::get('app.redirect') >= $maxRedirects) {
			$redir = Config::get('app.redirect');
			throw new Exception("Reached $redir redirections, which is the maximum");
		}
		Output::output();
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
	
	private $listeners = array();
	
	/**
	 * Adds a listener to the given event identifier. A listerner can be 
	 * any callable or an implementation of the IFilter interface.
	 * @see Kondoo\Filter\IFilter
	 * @param mixed $event
	 * @param mixed $filter
	 * @return boolean
	 */
	public function bind($event, $filter = null) 
	{
		if($event instanceof IEventFilter) {
			$filter = $event;
			$event = $filter->getEvents();
		}
		
		if(is_callable($filter) || $filter instanceof IFilter) {
			if(is_array($event)) {
				foreach($event as $loc) {
					if(!isset($this->listeners[$loc])) {
						$this->listeners[$loc] = array();
					}
					$this->listeners[$loc][] = $filter;
				}
			} else {
				if(!isset($this->listeners[$event])) {
					$this->listeners[$event] = array();
				}
				$this->listeners[$event][] = $filter;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Triggers all listeners that are waiting for events on the given location.
	 * @param string $event The event to trigger
	 * @param mixed $data Extra data to be submitted to the listeners
	 * @return boolean
	 */
	public function trigger($event, &$data = null)
	{
		$listeners = isset($this->listeners[$event]) ? $this->listeners[$event] : array();
		$result = true;
		foreach($listeners as $listener) {
			if(is_callable($listener)) {
				if(call_user_func_array($listener, array(&$data)) === false) {
					$result = false;
				}
			} else if($listener instanceof IFilter) {
				if($listener->call($this, $data) === false) {
					$result = false;
				}
			}
		}
		return $result;
	}
	
	public function redirect($controller, $action = null, $params = null)
	{
		$callController = $this->request->getController();
		$callAction = $this->request->getAction();
		$callParams = $this->request->params();
		if(is_string($controller) && is_string($action)) {
			$callController = $controller;
			$callAction = $action;
			if(is_array($params)) {
				$callParams = $params;
			}
		} else if(is_string($controller)) {
			if(strpos($controller, '/') !== false) {
				list($callController, $callAction) = explode('/', $controller, 2);
			} else {
				$callAction = $controller;
			}
			if(is_array($action)) {
				$callParams = $action;
			}
		}
		
		$this->request->setController($callController);
		$this->request->setAction($callAction);
		$this->request->setParams($callParams);
		$this->redirected = true;
	}
}