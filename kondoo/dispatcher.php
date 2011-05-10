<?php

namespace Kondoo;

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
			$reflector = new \ReflectionClass($controller);
			if($reflector->implementsInterface('Kondoo\\Controller\\IController')) {
				$object = new $controller();
				$object->setRequest($request);
				$object->setApplication($app);
				$app->trigger('before_action');
				$object->__beforeAction();
				
				
				$method = $reflector->getMethod($request->getAction());
				if($method->isPublic()) {
					$params = $request->params();
					
					$app->trigger('call_action');
					$output = $method->invokeArgs($object, self::matchParams($method, $params));
					
					$app->trigger('after_action', $output);
					return $object->__afterAction($output);
				} else {
					throw new \Exception("Method found, but is private");
				}
			} else {
				throw new \Exception("Controller found, but doesn't implement IController");
			}
		} else {
			throw new \Exception("Controller not found");
		}
	}
	
	/**
	 * Sort the parameters so that they fit onto the given method. Throws an exception if the
	 * function can't be called.
	 * @param \ReflectionMethod $method
	 * @param array $params
	 * @throws \Exception
	 */
	public static function matchParams(\ReflectionMethod $method, array $params)
	{
		$values = array();
		foreach($method->getParameters() as $param) {
			if(isset($params[$param->getName()])) {
				$values[] = $params[$param->getName()];
			} else if($param->isDefaultValueAvailable()) {
				$values[] = $param->getDefaultValue();
			} else {
				throw new \Exception("Not enough parameters to call action");
			}
		}
		return $values;
	}
}