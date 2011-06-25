<?php

namespace Kondoo\Request;

class Image {
	/**
	 * No scaling (same as cropping with (0, 0) as the origin)
	 * @var int
	 */
	const NO_SCALE = 1;
	
	/**
	 * Scale an image so that both its horizontal and vertical axis fit perfectly
	 * @var unknown_type
	 */
	const SCALE_FIT = 2;
	
	/**
	 * Scale an image using its horizontal axis
	 * @var int
	 */
	const SCALE_HORIZONTAL = 4;
	
	/**
	 * Scale an image using its vertical axis
	 * @var int
	 */
	const SCALE_VERTICAL = 8;
	
	/**
	 * Scale an image, but keep its ratio
	 * @var int
	 */
	const SCALE_RATIO = 16;
	
	public function __construct($location)
	{
		// TODO: use location to get some information about the image (or maybe: only if needed)
	}
	
	public static function fromFile(File $file)
	{
		// TODO: extract attributes from File and put them in Image class
	}
	
	public function move($location)
	{
		// TODO: move this image to the given location
	}
	
	public function duplicate($location)
	{
		// TODO: create a copy at the given location
	}
	
	public function resize($width, $height, $scale = self::SCALE_RATIO)
	{
		// TODO: resize the image to the given width and height, using the scale
	}
	
	public function crop($x, $y, $width, $height)
	{
		// TODO: crop the image to the given dimensions
	}
	
	public function cropAndResize($x, $y, $fromWidth, $fromHeight, $toWidth, 
		$toHeight, $scale = self::SCALE_RATIO)
	{
		// TODO: crop the image as done by crop, then immediately resize to the given width and height, using the scale
	}
	
	
}