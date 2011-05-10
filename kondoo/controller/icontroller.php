<?php

namespace Kondoo\Controller;

use Kondoo\Application;

use Kondoo\Request;

interface IController {
	/**
	 * Is called automatically to add the current request to the controller, so that it may be used
	 * in actions.
	 * @param Request $request
	 */
	public function setRequest(Request $request);
	
	/**
	 * Is called automatically to add the current application to the controller, so that it may
	 * be used in actions.
	 * @param Application $app
	 */
	public function setApplication(Application $app);
	
	/**
	 * Called just before the action is executed, note that any return value of this function is
	 * ignored.
	 */
	public function __beforeAction();
	
	/**
	 * Called after the action is executed, the output given as a parameter so that it may be
	 * changed.
	 * @param mixed $output
	 * @return The output
	 */
	public function __afterAction($output);
}