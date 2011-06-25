<?php

namespace Kondoo\Controller;

use Kondoo\Request;
use Kondoo\Application;
use Kondoo\Output;

class Controller implements IController {
	
	/**
     * The request that resulted in calling this controller
     * @var Kondoo\Request
	 */
	protected $request;
	
	/**
	 * The application that called this controller
	 * @var Kondoo\Application
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
	
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}
	
	public function setApplication(Application $app)
	{
		$this->app = $app;
	}
	
	public function __get($variable)
	{
		return Output::get($variable);
	}
	
	public function __set($variable, $value)
	{
		Output::set($variable, $value);
	}
}