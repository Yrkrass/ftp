# Moxar/Ftp

## Installation

This is a php library for ftp. It's based on the laravel framework.
To install, add the following lines to composer, then run composer update.

```javascript
	{
		...
		"require": {
			"moxar/ftp": "dev-master"
		},
		...
	}
```

Don't forget to add the FtpServiceProvider to your config/app.pphp file.
Also, I sugges you use an alias. The class responsible for the Ftp connexion is known as `connexion`.

```php
	./config/app.php

	return array(
		...
		'providers' => array(
			...
			'Moxar\Ftp\FtpServiceProvider',
			...
		),
		...
		'aliases' => array(
			...
			'Ftp' => 'Moxar/Ftp/Facades/Connexion',
			...
		),
		...
	);
```
	
## Configuration

The package behaves as the DB package provided by Illuminate. You need a config file like this one.

```php
	config/ftp.php
	
	return [
		'default' => 'some_connexion',				// This the key of the default connexion.
			
		'some_connexion' => [						// A connexion identified by a slug
			'host' => 'localhost',
			'port' => '21',
			'username' => 'my_user_name',
			'password' => 'my_password',
			'protocol' => 'ftp',
		],
		'some_other_connexion' => [					// Another connexion identified by another slug
			...
		],
	];
```

## Usage

### Ftp methods

Once installation and configuration done, you can use the package.
Here come some examples of usage.

	```php
	use Moxar\Ftp\Facades\Connexion as Ftp;
	
	// Connexion
	Ftp::connexion($connexion);			// switches the $connexion to the defined slug.
	
	// Uploads and Downloads
	Ftp::upload($local, $remote); 		// uploads the file or directory $local to the location $remote (recursive).
	Ftp::download($remote, $local);		// downloads the file or directory $remote to the location $local (recursive).
	
	// Readings
	Ftp::isDirectory($path);			// tells you if $path is a directory.
	Ftp::isFile($path);					// tells you if $path is a file.
	Ftp::exists($path);					// tells you if $path exists.
	Ftp::files($path); 					// returns all files and directories within $path as an array.
	
	// Writings
	Ftp::makedirectory($path);			// creates a directory at the $path location.
	Ftp::clean($path);					// removes all contents inside a $path directory (recursive).
	Ftp::delete($path);					// removes the file or directory from the given $path location (recursive).
	Ftp::move($old, $new);				// moves the $old file or directory to $new location.
	Ftp::copy($old, $new, $buffer)		// copies the content of $old to $new using a local $buffer folder.
```

### Vanilla methods

The library comes with all the php vanilla functions thanks to the `__call()` magic method.
Any vanilla ftp_method called will be called using the current connexion as `ftp_stream` argument.
Here are some example of usage.

	```php
	use Moxar\Ftp\Facades\Connexion as Ftp;
	
	Ftp::ftp_chmod(755, $file);
	Ftp::ftp_mkdir($path);
```
