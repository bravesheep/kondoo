<?php

namespace Kondoo;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use Kondoo\Controller\IController;
use Kondoo\Listener\Event;

class Dispatcher {
	const CONTROLLER_POSTFIX = 'Controller';
	
	/**
	 * Dispatches the request to the application to the accurate controller and action and calls
	 * functions required.
	 * @param Application $app
	 * @param Request $request
	 * @throws \Exception
	 */
	public static function dispatch(Application $app, Request $request)
	{
		$controller = $request->getController();
		$directory = Config::get('app.controllers');
		if($directory[0] === '.') {
			$directory = realpath(Config::get('app.location') . $directory) . DIRECTORY_SEPARATOR;
		}
		
		$controller .= self::CONTROLLER_POSTFIX;
		$file = $directory . $controller . '.php';
		
		if(file_exists($file) && is_readable($file)) {
			require_once $file;
			$reflector = new ReflectionClass($controller);
			if($reflector->implementsInterface('Kondoo\\Controller\\IController')) {
				$object = new $controller();
				$object->setRequest($request);
				$object->setApplication($app);
				$method = $reflector->getMethod($request->getAction());
				self::dispatchAction($method, $object, $request->params());
			} else {
				throw new Exception("Controller found, but doesn't implement IController");
			}
		} else {
			throw new Exception("Controller not found");
		}
	}
	
	/**
	 * Dispatch the 
	 * Enter description here ...
	 * @param ReflectionMethod $method
	 * @param IController $controller
	 * @param array $params
	 * @throws Exception
	 */
	private static function dispatchAction(ReflectionMethod $method, IController $controller, 
			array $params)
	{
		if($method->isPublic()) {	
			Event::trigger('before_action');
			$controller->__beforeAction();
			
			Event::trigger('call_action');
			$method->invokeArgs($controller, self::matchParams($method, $params));
			
			$controller->__afterAction();
			Event::trigger('after_action', $controller);
		} else {
			throw new Exception("Method for action found, but is not public.");
		}
	}
	
	/**
	 * Sort the parameters so that they fit onto the given method. Throws an exception if the
	 * function can't be called.
	 * @param \ReflectionMethod $method
	 * @param array $params
	 * @throws \Exception
	 */
	public static function matchParams(ReflectionMethod $method, array $params)
	{
		$values = array();
		foreach($method->getParameters() as $param) {
			if(isset($params[$param->getName()])) {
				$values[] = $params[$param->getName()];
			} else if($param->isDefaultValueAvailable()) {
				$values[] = $param->getDefaultValue();
			} else {
				throw new Exception("Not enough parameters to call action");
			}
		}
		return $values;
	}
}