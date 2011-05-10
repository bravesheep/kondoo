<?php

namespace Kondoo\Filter;
/**
 * IFilter that defines its own events to be registered.
 */
interface IEventFilter extends IFilter {
	/**
	 * Returns either a string or array of strings indicating which events this filter wants to
	 * register itself at. 
	 * @return mixed
	 */
	public function getEvents();
}