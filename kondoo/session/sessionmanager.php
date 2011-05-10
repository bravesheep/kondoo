<?php

namespace Kondoo\Session;

use Kondoo\Filter\IEventFilter;
use Kondoo\Application;

class SessionManager implements IEventFilter {
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