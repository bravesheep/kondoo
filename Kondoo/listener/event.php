<?php

namespace Kondoo\Listener;

class Event {
	private static $listeners = array();
	
	/**
	 * Adds a listener to the given event identifier. A listerner can be 
	 * any callable or an implementation of the IFilter interface.
	 * @see Kondoo\Listener\IListener
	 * @param mixed $event
	 * @param mixed $listener
	 * @return boolean
	 */
	public static function bind($event, $listener = null) 
	{
		if($listener instanceof IEventListener) {
			$listener = $event;
			$event = $listener->getEvents();
		}
		
		if(is_callable($listener) || $listener instanceof IListener) {
			if(is_array($event)) {
				foreach($event as $loc) {
					if(!isset(self::$listeners[$loc])) {
						self::$listeners[$loc] = array();
					}
					self::$listeners[$loc][] = $listener;
				}
			} else {
				if(!isset(self::$listeners[$event])) {
					self::$listeners[$event] = array();
				}
				self::$listeners[$event][] = $listener;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Triggers all listeners that are waiting for events on the given location.
	 * @param string $event The event to trigger
	 * @param mixed $data Extra data to be submitted to the listeners
	 * @return boolean
	 */
	public static function trigger($event, &$data = null)
	{
		$listeners = isset(self::$listeners[$event]) ? self::$listeners[$event] : array();
		$result = true;
		foreach($listeners as $listener) {
			if(is_callable($listener)) {
				if(call_user_func_array($listener, array(&$data)) === false) {
					$result = false;
				}
			} else if($listener instanceof IFilter) {
				if($listener->call($this, $data) === false) {
					$result = false;
				}
			}
		}
		return $result;
	}
}