<?php

namespace Kondoo\Controller;

use Kondoo\Request;
use Kondoo\Application;
use Kondoo\Output;

class BaseController implements Controller {
	
	/**
     * The request that resulted in calling this controller
     * @var \Kondoo\Request
	 */
	protected $request;
	
	/**
	 * The response for output.
	 * @var \Kondoo\Response
	 */
	protected $response;
	
	/**
	 * The application that called this controller
	 * @var \Kondoo\Application
	 */
	protected $app;
	
	public function before()
	{
		// default implementation does nothing
	}
	
	public function after()
	{
		// default implementation does nothing
	}
	
	public function init()
	{
	    // default implementation does nothing
	}
	
	public function app(Application $app = null)
	{
	    if($app !== null) {
	        $this->app      = $app;
    		$this->request  = $this->app->request;
    		$this->response = $this->app->response;
	    }
	    return $this->app;
	}
	
	/**
	 * Calls getter on the response object, used for response variables.
	 * @see \Kondoo\Response::get()
	 * @param string $variable Response variable to retrieve.
	 * @return mixed Content of the variable.
	 */
	public function __get($variable)
	{
		return $this->response->get($variable);
	}
	
	/**
	 * Calls setter on the response object, used for response variables.
	 * @see \Kondoo\Response::set()
	 * @param string $variable Response variable to set.
	 * @param mixed $value New value for the given response variable.
	 * @return void
	 */
	public function __set($variable, $value)
	{
		$this->response->set($variable, $value);
	}
}