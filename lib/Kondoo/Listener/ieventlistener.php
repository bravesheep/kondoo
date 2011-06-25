<?php

namespace Kondoo\Listener;
/**
 * IFilter that defines its own events to be registered.
 */
interface IEventListener extends IListener {
	/**
	 * Returns either a string or array of strings indicating which events this filter wants to
	 * register itself at. 
	 * @return mixed
	 */
	public function getEvents();
}