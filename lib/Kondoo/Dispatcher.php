<?php

namespace Kondoo;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use \Kondoo\Controller\Controller;
use \Kondoo\Listener\Event;
use \Kondoo\Resource\Provider;
use \Kondoo\Response\Redirect;
use \Kondoo\Util\PathUtil;

class Dispatcher implements Provider {
	const CONTROLLER_POSTFIX = 'Controller';
	const ACTION_POSTFIX = "Action";
	const PHP_EXT = ".php";
	
	private $redirect;
	
	private $app;
	
	public function __construct()
	{
	    $this->redirect = null;
	}
	
	public function setOptions(array $options)
	{
	    // No options required.
	}
	
	
	private function updateRequest()
	{
	    $this->redirect->setRequest($this->app->request);
	    $this->app->request->setModule($this->redirect->getModule());
	    $this->app->request->setController($this->redirect->getController());
	    $this->app->request->setAction($this->redirect->getAction());
	    $this->app->request->setParams($this->redirect->getParams());
	}
	
	private function getControllerDir()
	{
	    $directory = Options::get('app.dir.controllers');
	    $directory = str_replace('%MODULE%', $this->app->request->getModule(), $directory);
	    $directory = realpath(PathUtil::expand($directory));
	    if($directory === false) {
	        throw new Exception(sprintf(
	            _("Controller folder not found for module %s"), 
	            $this->app->request->getModule()
	        ));
	    }
	    return $directory . DIRECTORY_SEPARATOR;
	}
	
	private function loadController($controllerName)
	{
	    $directory = $this->getControllerDir();
	    $file = $directory . $controllerName . self::PHP_EXT;
	    if(!file_exists($file) || !is_readable($file)) {
	        throw new Exception(sprintf(
	            _("Controller %s not found, or not readable"), 
	            $controllerName
	        ));
	    } else {
	        require_once $file;
	        if(!class_exists($controllerName)) {
	            throw new Exception(sprintf(
	                _("File for controller %s found, but no class with the same name"),
	                $controllerName
	            ));
	        }
	    }
	}
	
	private function constructController($controller)
	{
	    $reflector = new ReflectionClass($controller);
	    if($reflector->implementsInterface('\\Kondoo\\Controller\\Controller')) {
	        $object = new $controller();
	        $object->app($this->app);
	        $object->init();
	        Event::trigger('controller_init', $object);
	        return $object;
	    } else {
	        throw new Exception(sprintf(
			    _("Controller '%s' doesn't implement interface Controller"),
			    $controller
			));
	    }
	}
	
	public function dispatch(Application $app)
	{
	    $this->app = $app;
	    $controllers = array();
	    do {
	        if(!is_null($this->redirect)) {
	            $this->updateRequest();
	            $this->redirect = null;
	        }
	        
    		$controller = $app->request->getController() . self::CONTROLLER_POSTFIX;
    		if(!class_exists($controller)) {
    		    $this->loadController($controller);
    		}
    		
    		if(!isset($controllers[$controller]) || 
    		        !($controllers[$controller] instanceof $controller)) {
    		    $controllers[$controller] = $this->constructController($controller);
    		}
    		
    		$object = $controllers[$controller];
    		$this->dispatchAction($object);
	    } while(!is_null($this->redirect));
	}
	
	private function dispatchAction(Controller $controller)
	{
	    $action = $this->app->request->getAction();
	    $methodName = self::toActionMethod($action);
	    $reflector = new ReflectionClass($controller);
	    if(!$reflector->hasMethod($methodName)) {
	        throw new Exception(sprintf(_("No method for action '%s'."), $action));
	    }
	    
	    $method = $reflector->getMethod($methodName);
	    if(!$method->isPublic()) {
	        throw new Exception(sprintf(_("Method for action '%s' is not public."), $action));
	    }
	    
	    Event::trigger('before_action', $controller);
	    $controller->before();
	    
	    Event::trigger('call_action', $controller);
	    $params = $this->app->request->params();
	    $result = $method->invokeArgs($controller, self::matchParams($method, $params));
	    if($result instanceof Redirect) {
	        $this->redirect = $result;
	    }
	    
	    $controller->after();
	    Event::trigger('after_action', $controller);
	}
	
	public static function toActionMethod($methodName)
	{
	    return $methodName . self::ACTION_POSTFIX;
	}
	
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