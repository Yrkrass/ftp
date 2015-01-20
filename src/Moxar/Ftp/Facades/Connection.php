<?php namespace Moxar\Ftp\Facades;

use Illuminate\Support\Facades\Facade;

class Connection extends Facade {

	protected static function getFacadeAccessor() 
	{ 
		return 'moxar.connection'; 
	}
}
