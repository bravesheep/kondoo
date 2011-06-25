<?php

namespace Kondoo\Request;

class File {
	public function __construct(array $data)
	{
		// TODO: use the file upload data in $data to build a file class
	}
	
	public function storeAt($location)
	{
		// TODO: store the file at the given location
	}
	
	public function isType($type)
	{
		// TODO: return true if the type of this file is $type
	}
	
	public function isImage($accepted = array('gif', 'jpg', 'png'))
	{
		// TODO: do some extra checks to see if this is an image
	}
	
	public function image() 
	{
		// TODO: convert this file to an image (only if it actually is an image
	}
	
	public function getName()
	{
		// TODO: return the name of the file as uploaded
	}
	
	public function getType()
	{
		// TODO: return the type of the file as provided by the uploaders browser
	}
}
