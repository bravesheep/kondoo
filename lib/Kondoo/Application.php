<?php

namespace Kondoo;

use \Kondoo\Filter\IFilter;
use \Exception;
use \Kondoo\Resource\Provider;
use \Kondoo\Util\ArrayUtil as ArrayUtil;

use \Kondoo\Request;
use \Kondoo\Response;

/**
 * The base application class. All applications should extend from this class.
 */
abstract class Application {
    
    const SETUP_METHOD_IDENTIFIER = "setup";
    
	/**
	 * Contains the latest instance of the application after the run function has dispatched
	 * the request to a dispatcher
	 * @var Kondoo\Application
	 */
	private static $application = null;
	
	public static function init($environment)
	{
	    static::setBaseOptions($environment);
	    
	    $app = new static();
	    self::$application = $app;
	    $app->beforeSetup();
	    
	    date_default_timezone_set(Options::get('app.timezone', 'UTC'));
	    
	    $app->runSetupFunctions();
	    return $app;
	}
	
	/**
	 * This method sets some basic options such as the app location and public folder. These
	 * options may be overwritten in a config file, but in most cases the default locations
	 * should suffice.
	 */
	protected static function setBaseOptions($environment)
	{
	    Options::set('app.env', $environment);
	    
	    $script = $_SERVER['SCRIPT_FILENAME'];
		Options::set('app.location', dirname(dirname($script)) . DIRECTORY_SEPARATOR .
					'application');
		Options::set('app.public', dirname($script));
		
		Options::set('app.dir.controllers', './controllers');
		Options::set('app.dir.templates', './templates');
		Options::set('app.dir.locales', './locale');
		Options::set('app.dir.models', './models');
		Options::set('output.late', true);
		
		Options::set('app.resources.dispatcher', "Kondoo\\Dispatcher");
		Options::set('app.resources.router', "Kondoo\\Router");
	}
	
	/**
	 * Return the name formatted such that it is a valid resource name.
	 * @param string $name The string to format as a resource name.
	 * @return string
	 */
	public static function toResourceName($name)
	{
	    return strtoupper($name[0]) . substr($name, 1);
	}
	
	private $invoked = array();
	private $resources = array();
	
	public $request;
	public $response;
	
	/**
	 * Run all setup methods in the Application. Note that some setup functions may require some
	 * resource to be constructed. The construction of a method may 
	 */
	private function runSetupFunctions()
	{
	    $this->initialized = array();
	    
	    $reflector = new \ReflectionClass($this);
	    $mid = self::SETUP_METHOD_IDENTIFIER;
	    $methods = array_filter($reflector->getMethods(), function($method) use($mid) {
	        return strpos($method->getName(), $mid) === 0 &&
	            $method->getNumberOfRequiredParameters() === 0;
	    });
	    
	    foreach($methods as $method) {
	        $resourceName = substr($method->getName(), strlen(self::SETUP_METHOD_IDENTIFIER));
	        if(!$this->hasResource($resourceName)) {
	            $this->invoked[$method->getName()] = true;
    	        $resource = $this->{$method->getName()}();
    	        if(isset($resource) && $resource) {
    	            $this->resources[$resourceName] = $resource;
    	        }
    	        $this->invoked[$method->getName()] = false;
            }
	    }
	}
	
	/**
	 *
	 */
	public function hasResource($name) {
	    $name = self::toResourceName($name);
	    return isset($this->resources[$name]) && $this->resources[$name];
	}
	
	/**
	 * Return a resource for the given name. If the resource is not yet initialized, it is 
	 * automatically initialized. If a setup method for the same resource as the requested resource
	 * is currently running, the framework searches for another object providing the resource.
	 */
	public function resource($name)
	{
	    $name = self::toResourceName($name);
	    if(!$this->hasResource($name)) {
	        $methodName = self::SETUP_METHOD_IDENTIFIER . $name;
	        $reflector = new \ReflectionClass($this);
	        if($reflector->hasMethod($methodName) && !$this->invoked[$methodName]) {
	            $methodReflector = $reflector->getMethod($methodName);
	            
	            $this->invoked[$methodName] = true;
                $resource = $this->{$methodReflector->invoke()}();
                $this->invoked[$methodName] = false;

                if($resource) {
                    $this->resources[$name] = $resource;
                } else {
                    throw new \Exception(sprintf(
                        _("Resource method %s returned no resource."), 
                        $resourceName
                    ));
                }
	        } else {
	            $this->initResource($name);
	        }
	        
	    }
	    return $this->resources[$name];
	}
	
	/**
	 * Initialize a resource that extends from the Kondoo\Resource\Provider interface
	 * and that is configured using the app.resources configuration option in the config
	 * file.
	 * @see \Kondoo\Resource\Provider
	 * @param string $name The name of the resource to initialize.
	 * @return void
	 */
	private function initResource($name)
	{
	    $resourceName = self::toResourceName($name);
	    
	    $resourceProviders = Options::get('app.resources', array());
        $first = ArrayUtil::firstKey($resourceProviders, 
            function($key) use ($resourceName)  {
                return strtolower($key) === strtolower($resourceName);
            }
        );
        
        if(!is_null($first)) {
            $resourceClass = $resourceProviders[$first];
            $resource = new $resourceClass;
            if(!($resource instanceof Provider)) {
                throw new \Exception(sprintf(
                    _("%s is not an instance of %s"),
                    $resourceClass,
                    "Kondoo\\Resource\\Provider"
                ));
            }
            $resourceOptions = Options::get($first, array());
            $resource->setOptions($resourceOptions);
            $this->resources[$resourceName] = $resource;
        } else {
            throw new \Exception(sprintf(
                _("%s is not a valid resource, or doesn't exist."), 
                $resourceName
            ));
        }
	}
	
	/**
	 * Shorthand for the resource method, using PHP magic methods.
	 * @see resource()
	 * @param string $name The name of a resource.
	 * @return mixed The requested resource.
	 */
	public function __get($name)
	{
	    return $this->resource($name);
	}
	
	/**
	 * Shorthand for the hasResource method, using PHP magic methods.
	 * @see hasResource()
	 * @param string $name The name of a resource.
	 * @return boolean True if the resource exists and is initialized, false otherwise.
	 */
	public function __isset($name)
	{
	    return $this->hasResource($name);
	}
	
	/**
	 * Default implementation: does nothing.
	 */
	protected function beforeSetup()
	{
	    Options::load('config.yaml');
	}
	
	/**
	 * Run the application, call this static method on the application that should be run.
	 * After all internal redirects are handled, the output is generated.
	 * @return void
	 */
	public function run() {
		if(!isset($this->router)) {
		    $this->router = new Router(array(
		        ''
		    ));
		}
		
		$this->request  = new Request($this);
		$this->response = new Response(); 
		
		$this->dispatcher->dispatch($this);
		
		$this->response->output();
	}
	
	/**
	 * Get the latest created application instance.
	 * @return \Kondoo\Application
	 */
	public static function get()
	{
		return self::$application;
	}
	
	/**
	 * Redirect to the given controller/action pair. If this is called from within an action,
	 * the current action will first finish, but any generated output that is still cached will
	 * be removed.
	 * @param string $controllerAction The controller/action pair.
	 * @param array $params Either a single parameter or an array of parameters.
	 * @return void
	 */
	public function redirect($controllerAction, $params = null)
	{
		$callController = $this->request->getController();
		$callAction = $this->request->getAction();
		$callParams = $this->request->params();
		if(strpos($controllerAction, '/') !== false) {
			list($callController, $callAction) = explode('/', $controller, 2);
		} else {
			$callAction = $controller;
		}
		
		if(!is_array($params) && !is_null($params)) {
		    $callParams = array($params);
		} else if(is_array($params)) {
		    $callParams = $params;
		}
		
		$this->request->setController($callController);
		$this->request->setAction($callAction);
		$this->request->setParams($callParams);
		$this->redirected = true;
		return $this;
	}
}