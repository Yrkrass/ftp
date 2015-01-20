# Moxar/Ftp

## Installation

This is a php library for ftp. It's based on the laravel framework.
To install, add the following lines to composer, then run composer update.

./composer.json
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
Also, I suggest you use an alias. The class responsible for the Ftp connection is known as `connection`.

./config/app.php
```php

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
			'Ftp' => 'Moxar/Ftp/Facades/Connection',
			...
		),
		...
	);
```
	
## Configuration

The package behaves as the DB package provided by Illuminate. You need a config file like this one.

./config/ftp.php
```php
	
	return [
		'default' => 'some_connection',				// This the key of the default connection.
		
		'connections' => [
			
			'some_connection' => [					// A connection identified by a slug
				'host' => 'localhost',
				'port' => '21',
				'username' => 'my_user_name',
				'password' => 'my_password',
				'protocol' => 'ftp',				// You can use ftp, sftp or ftps.
													// However, sftp and ftps are not well implemented. Expect bugs.
			],
			'some_other_connection' => [			// Another connection identified by another slug
				...
			],
		],
	];
```

## Usage

### Ftp methods

Once installation and configuration done, you can use the package.
Here come some examples of usage.

```php
	use Moxar\Ftp\Facades\Connection as Ftp;
	
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
	
	// Connection
	Ftp::connection($connection)
		->upload($local, $remove);		// switches the $connection to the defined slug and uses the upload method.
```

### Vanilla methods

The library comes with all the php vanilla functions thanks to the `__call()` magic method.
Any vanilla ftp_method called will be called using the current connection as `ftp_stream` argument.
Here are some example of usage.

```php
	use Moxar\Ftp\Facades\Connection as Ftp;
	
	Ftp::ftp_chmod(755, $file);
	Ftp::ftp_mkdir($path);
```
