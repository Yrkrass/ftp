<?php namespace Moxar\Ftp\Facades;

use Illuminate\Support\Facades\Facade;

class Connexion extends Facade {

	protected static function getFacadeAccessor() 
	{ 
		return 'moxar.connexion'; 
	}
}
