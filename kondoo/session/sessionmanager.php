<?php

namespace Kondoo\Session;

use Kondoo\Listener\IEventListener;
use Kondoo\Application;

class SessionManager implements IEventListener {
	public function getEvents() 
	{
		return 'dispatch';
	}
	
	public function call(Application $app, &$data)
	{
		$request = $app->request;
		// return Session::hasAccess($request->getController(), $request->getAction());
		return true;
	}
}