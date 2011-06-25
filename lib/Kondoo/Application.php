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
 * The extended class may contain any number of setup* methods, where * is a
 * camel cased resource name. Use the {@link beforeSetup()} method to initialize
 * settings you need to configure before any setup method is executed. This may be mainly
 * used for loading a configuration file. Note that before and during setup, the request
 * and response are not yet available.
 */
abstract class Application {
    /**
     * Method prefix for setup functions.
     * @var string
     */
    const SETUP_METHOD_IDENTIFIER = "setup";
    
	/**
	 * Contains the latest instance of the application after the run function has dispatched
	 * the request to a dispatcher
	 * @var \Kondoo\Application
	 */
	private static $application = null;
	
	/**
	 * Initialize a new application. This sets all the base options using {@link setBaseOptions()},
	 * then calls the {@link beforeSetup} method, and finally calls all setup methods. This 
	 * function also sets the default timezone, preventing PHP error messages.
	 * @param string $environment The environment (eg. production, development)
	 * @return void
	 */
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
	 * @param string $environment The environment (eg. production, development)
	 * @return void
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
	
	/**
	 * Names of resources that are currently being invoked.
	 * @var array
	 */
	private $invoked = array();
	
	/**
	 * Currently loaded resources.
	 * @var array
	 */
	private $resources = array();
	
	/**
	 * The currently active request. Only active during a call to {@link run()}.
	 * @var \Kondoo\Request
	 */
	public $request;
	
	/**
	 * The current response output. Only active during a call to {@link run()}.
	 * @var \Kondoo\Response
	 */
	public $response;
	
	/**
	 * Return all methods that qualify as a setup method.
	 * @return array An array of \RelfectionMethod objects.
	 */
	private function getSetupMethods()
	{
	    $reflector = new \ReflectionClass($this);
	    $mid = self::SETUP_METHOD_IDENTIFIER;
	    return array_filter($reflector->getMethods(), function($method) use($mid) {
	        return strpos($method->getName(), $mid) === 0 &&
	            $method->getNumberOfRequiredParameters() === 0;
	    });
	}
	
	/**
	 * Run all setup methods in the Application. If a resource setup function recursively tries to
	 * call itself, it is prevented from doing so, after checking if a Provider resource might be
	 * constructed using {@link initResource()}. If a setup function returns a value that
	 * evaluates to true in PHP, it is interpreted as a resource with the same name as the setup
	 * function. Otherwise the value is ignored.
	 * @see hasResource()
	 * @see initResource()
	 * @see resource()
	 * @see \Kondoo\Resource\Provider
	 * @return void
	 */
	private function runSetupFunctions()
	{
	    $this->initialized = array();
	    
	    $methods = $this->getSetupMethods();
	    foreach($methods as $method) {
	        $resourceName = substr($method->getName(), strlen(self::SETUP_METHOD_IDENTIFIER));
	        $resourceName = self::toResourceName($resourceName);
	        
	        if(!$this->hasResource($resourceName)) {
	            $this->invoked[$method->getName()] = true;
    	        $resource = $this->{$method->getName()}();
    	        if($resource) {
    	            $this->resources[$resourceName] = $resource;
    	        }
    	        $this->invoked[$method->getName()] = false;
            }
	    }
	}
	
	/**
	 * Return true if a resource with the given name exists and is initialized (and ready to use).
	 * @param string $name The name of the resource
	 * @return boolean True if the resource exists and is initialized, false otherwise.
	 */
	public function hasResource($name) {
	    $name = self::toResourceName($name);
	    return isset($this->resources[$name]) && $this->resources[$name];
	}
	
	/**
	 * Return a setup method for the given (resource) name. A setup method is a match if the lower
	 * case versions of the method name and the constructed method name (from the given name) are
	 * the same.
	 * @param string $name The name of a setup method (without the setup prefix).
	 * @return \ReflectionMethod The matching method, or null if none was found.
	 */
	private function getSetupMethod($name)
	{
	    $name = self::toResourceName($name);
	    $methods = $this->getSetupMethods();
	    $fullMethodName = strtolower(self::SETUP_METHOD_IDENTIFIER . $name);
	    
	    foreach($methods as $method) {
	        if(strtolower($method->getName()) === $fullMethodName) {
	            return $method;
	        }
	    }
	    return null;
	}
	
	/**
	 * Return a resource for the given name. If the resource is not yet initialized, it is 
	 * automatically initialized. If a setup method for the same resource as the requested resource
	 * is currently running, the framework searches for another object providing the resource.
	 * @param string $name The name of the resource.
	 * @return mixed The value of the requested resource.
	 */
	public function resource($name)
	{
	    $name = self::toResourceName($name);
	    if(!$this->hasResource($name)) {
	        $method = $this->getSetupMethod($name);
	        if(!is_null($method) && !$this->invoked[$method->getName()]) {	            
	            $this->invoked[$method->getName()] = true;
                $resource = $this->{$method->getName()}();
                $this->invoked[$method->getName()] = false;

                if($resource) {
                    $this->resources[$name] = $resource;
                } else {
                    $this->initResource($name);
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
	    $configResourceName = strtolower($resourceName);
        $first = ArrayUtil::firstKey($resourceProviders, 
            function($key) use ($configResourceName)  {
                return strtolower($key) === $configResourceName;
            }
        );
        
        if(!is_null($first)) {
            $resourceClass = $resourceProviders[$first];
            $resource = new $resourceClass;
            if(!($resource instanceof Provider)) {
                throw new \Exception(sprintf(
                    _("%s is not an instance of %s"),
                    $resourceClass,
                    "\\Kondoo\\Resource\\Provider"
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
	 * Shorthand for the {@link resource()} method, using PHP magic methods.
	 * @see resource()
	 * @param string $name The name of a resource.
	 * @return mixed The requested resource.
	 */
	public function __get($name)
	{
	    return $this->resource($name);
	}
	
	/**
	 * Shorthand for the {@link hasResource()} method, using PHP magic methods.
	 * @see hasResource()
	 * @param string $name The name of a resource.
	 * @return boolean True if the resource exists and is initialized, false otherwise.
	 */
	public function __isset($name)
	{
	    return $this->hasResource($name);
	}
	
	/**
	 * This function is called just before all setup methods are called. Use it to set configuration
	 * settings and similar. Default implementation: tries to load the config.yaml file.
	 * @return void
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
