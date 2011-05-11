<?php

namespace Kondoo;

use Kondoo\Request\File;

class Request {
	/**
	 * Name of the called controller
     * @var string
	 */
	private $controllerName;
	
	/**
	 * Name of the called action
	 * @var string
	 */
	private $actionName;
	
	/**
	 * The application surrounding this request
	 * @var Kondoo\Application
	 */
	private $application;
	
	/**
	 * Parameters added to the request as a mapping from string names to string values
	 * @var array
	 */
	private $parameters = array();
	
	/**
	 * Constructs a new request given the application that constructs it
	 * @param Application $app
	 */
	public function __construct(Application $app) 
	{
		$this->application = $app;
		
		list($url) = explode('?', $_SERVER['REQUEST_URI']);
		$app->trigger('route', $url);
		
		$routed = $app->router->route($url);
		$app->trigger('routed', $routed);
		
		$this->controllerName = $routed['controller'];
		$this->actionName = $routed['action'];
		$this->parameters = $routed['params'];
	}
	
	/**
	 * Return true if this was a POST HTTP request
	 * @return boolean
	 */
	public function isPost()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}
	
	/**
	 * Return true if this was a GET HTTP request
	 * @return boolean
	 */
	public function isGet()
	{
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}
	
	/**
	 * Use the magic get method to retrieve query parameters passed in the url.
	 * @param string $variable The variable from the query parameters to retrieve
	 * @return mixed Array if multiple values for the same parameter were passed, string otherwise.
	 */
	public function __get($variable)
	{
		return $this->get($variable, null);
	}
	
	/**
	 * Return the query parameter passed via the url with the given name. If this name doesn't exist
	 * the default value is returned.
	 * @param string $variable The query parameter to retrieve
	 * @param mixed $default The value to return if the parameter doesn't exist
	 * @return mixed Array if multiple values for the same parameter were passed, string otherwise.
	 */
	public function get($variable, $default = null)
	{
		if(isset($_GET[$variable])) {
			return $_GET[$variable];
		}
		return $default;
	}
	
	/**
	 * Return the value of the parameter passed via the POST payload. If this name doesn't exist
	 * the default value is returned.
	 * @param string $variable The parameter to retrieve
	 * @param mixed $default The value to return if the parameter doesn't exist
	 * @return mixed Array if multiple values for the same parameter were passed, string otherwise.
	 */
	public function post($variable, $default = null)
	{
		if(isset($_POST[$variable])) {
			return $_POST[$variable];
		}
		return $default;
	}
	
	/**
	 * Returns a file object for the file uploaded via the POST parameter with the given name. If
	 * the file wasn't uploaded, null is returned. To check if the file actually exists please
	 * use the File object.
	 * @param string $variable The file to retrieve
	 * @return Kondoo\Request\File File that is uploaded within the given parameter of the HTTP POST
	 */
	public function file($variable)
	{
		if(isset($_FILES) && isset($_FILES[$variable])) {
			return new File($_FILES[$variable]);
		}
		return null;
	}
	
	/**
	 * Return a cookie with the given variable name
	 * @param string $variable
	 * @param mixed $default
	 * @return mixed
	 */
	public function cookie($variable, $default = null)
	{
		if(isset($_COOKIE[$variable])) {
			return $_COOKIE[$variable];
		}
		return $default;
	}
	
	/**
	 * Retrieve the parameter with the given variable name
	 * @param string $variable The name of the parameter to search for
	 * @param mixed If the parameter with the given name doesn't exist $default, otherwise the value
	 */
	public function param($variable, $default = null)
	{
		if(isset($this->parameters[$variable])) {
			return $this->parameters[$variable];
		}
		return $default;
	}
	
	/**
	 * Return the array containing all parameters (the array maps from variable names to values)
	 * @return array
	 */
	public function params() 
	{
		return $this->parameters;
	}
	
	/**
	 * Return the requested controller
	 */
	public function getController()
	{
		return ucfirst($this->controllerName);
	}
	
	/**
	 * Return the requested action
	 */
	public function getAction()
	{
		return strtolower($this->actionName);
	}
	
	/**
	 * Set the controller name to the given controller.
	 * @param string $controller
	 */
	public function setController($controller)
	{
		$this->controllerName = $controller;
	}
	
	/**
	 * Set the action name to the given action
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->actionName = $action;
	}
	
	/**
	 * Change the parameters to be used when calling actions to the given array of params
	 * @param array $params
	 */
	public function setParams(array $params)
	{
		$this->parameters = $params;
	}
}