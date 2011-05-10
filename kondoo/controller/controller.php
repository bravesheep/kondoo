<?php

namespace Kondoo\Controller;

use Kondoo\Request;
use Kondoo\Application;

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
		
	}
	
	public function __afterAction($output)
	{
		return $output;
	}
	
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}
	
	public function setApplication(Application $app)
	{
		$this->app = $app;
	}
}