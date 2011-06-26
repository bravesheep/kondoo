<?php

namespace Kondoo\Controller;

use Kondoo\Application;
use Kondoo\Request;

/**
 * The interface controllers should implement. Controllers are given the current application object,
 * after which the {@link before()} method is called. After calling the {@link before()} method,
 * the requested action is called. Finally, the {@link after()} method is called to finish action
 * calling.
 */
interface Controller {	
	/**
	 * Called just before the action is executed, note that any return value of this function is
	 * ignored.
	 * @return void
	 */
	public function before();
	
	/**
	 * Called after the action is executed, the output given as a parameter so that it may be
	 * changed.
	 * @return void
	 */
	public function after();
	
	public function init();
	
	/**
	 * Is called automatically to add the current application to the controller, so that it may
	 * be used in actions.
	 * @param \Kondoo\Application $app
	 * @return void
	 */
	public function app(Application $app = null);
}