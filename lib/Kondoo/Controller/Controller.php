<?php

namespace Kondoo\Controller;

use Kondoo\Request;
use Kondoo\Application;
use Kondoo\Output;

class Controller implements IController {
	
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
	
	public function __beforeAction()
	{
		// default implementation does nothing
	}
	
	public function __afterAction()
	{
		// default implementation does nothing
	}
	
	public function setApplication(Application $app)
	{
		$this->app      = $app;
		$this->request  = $this->app->request;
		$this->response = $this->app->response;
	}
	
	public function __get($variable)
	{
		return $this->response->get($variable);
	}
	
	public function __set($variable, $value)
	{
		$this->response->set($variable, $value);
	}
}