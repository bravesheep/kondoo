<?php

namespace Kondoo\Filter;

use Kondoo\Application;

/**
 * Objects that want to be used for events throughout the framework should implement this interface.
 */
interface IFilter {
	/**
	 * Execute the filter given the application and extra data given via the second parameter. The
	 * return value for this method depends on the event that was the cause of calling this event.
	 * @param Application $app The application that called this filter
	 * @param array $data An array that might be filled with extra data to be used
	 */
	public function call(Application $app, &$data);
}