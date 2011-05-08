<?php

namespace Kondoo;

class Request {
	
	public $controllerName;
	
	public $actionName;
	
	private $application;
	
	private $parameters = array();
	
	public function __construct(Application $app) 
	{
		$this->application = $app;
	}
	
	public function setParams(array $params)
	{
		$this->parameters = $params;
	}
	
	public function __get($variable)
	{
		return $this->get($variable, null);
	}
	
	public function isPost()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}
	
	public function isGet()
	{
		return $_SERVEr['REQUEST_METHOD'] === 'GET';
	}
	
	public function get($variable, $default = null)
	{
		// TODO: return get value for the given variable in the get array
	}
	
	public function post($variable, $default = null)
	{
		// TODO: return post value for the given variable in the post array
	}
	
	public function file($variable)
	{
		// TODO: create file object of uploaded file in given variable name
	}
	
	public function cookie($variable, $default)
	{
		// TODO: return the value of the cookie with the given variable name
	}
}