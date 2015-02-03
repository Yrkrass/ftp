<?php namespace Moxar\Ftp;

use Illuminate\Support\Facades\Config;
use Exception;

class Connection {

	/*
	 * address
	 */
	protected $protocol;
	protected $host;
	protected $port;
	
	/*
	 * Credentials
	 */
	protected $username;
	protected $password;
	
	/*
	 * Resource
	 */
	protected $resource;
	
	/*
	 * Misc
	 */
	protected $connection;
	
	const PROTOCOL_FTP = 'ftp';
	const PROTOCOL_SSL = 'ftps';
	const PROTOCOL_SFTP = 'sftp';

	public function __construct()
	{
		$this->connection(Config::get('ftp.default'));
	}
	
	/*
	 * Sets the protected attributes according to config file
	 */
	public function connection($connection)
	{
		$this->connection = $connection;
		$this->host = Config::get('ftp.connections.'.$connection.'.host');
		$this->port = Config::get('ftp.connections.'.$connection.'.port');
		$this->username = Config::get('ftp.connections.'.$connection.'.username');
		$this->password = Config::get('ftp.connections.'.$connection.'.password');
		$this->protocol = Config::get('ftp.connections.'.$connection.'.protocol');
		
		$this->close();
		switch($this->protocol) {
			case self::PROTOCOL_FTP:
				$this->ftpConnect();
				break;
			case self::PROTOCOL_SSL:
				$this->sslConnect();
				break;
			case self::PROTOCOL_SFTP:
				throw(new Exception('SFTP protocol not suported yet.'));
				break;
			default:
				throw(new Exception('Unsuported protocol .'.$this->protocol));
		}
		
		return $this;
	}
	
	/*
	 * Attempts to establish a ssl-ftp connection
	 */
	public function sslConnect()
	{
		try {
			$this->resource = ftp_ssl_connect($this->host, $this->port);
		}
		catch(Exception $e) {
			throw(new Exception("Unable to establish a SSL-FTP connection to host: ".$this->host.':'.$this->port));
		}
		$this->login();
	}
	
	/*
	 * Attempts to establish a ftp connection
	 */
	public function ftpConnect()
	{
		try {
			$this->resource = ftp_connect($this->host, $this->port);
		}
		catch(Exception $e) {
			throw(new Exception("Unable to establish a FTP connection to host: ".$this->host.':'.$this->port));
		}
		$this->login();
	}
	
	/*
	 * Attempts to login a ftp connection
	 */
	public function login()
	{
		try {
			ftp_login($this->resource, $this->username, $this->password);
		}
		catch(Exception $e) {
			throw(new Exception("Wrong credentials for Ftp connection: ".$this->host.':'.$this->port));
		}
	}
	
	/*
	 * Tells if the given path is a directory
	 */
	public function isDirectory($path)
	{
		return is_dir($this->url().$path);
	}
	
	
	/*
	 * Tells if the given path is a file
	 */
	public function isFile($path)
	{
		return is_file($this->url().'/'.$path);
	}
	
	/*
	 * Lists all directories of the given path
	 */
	public function files($path = './')
	{
		return ftp_nlist($this->resource, $path);
	}
	
	/*
	 * Creates a directory at the given path
	 */
	public function makeDirectory($path)
	{
		ftp_mkdir($this->resource, $path);
	}
	
	/*
	 * Tells if the given path exists
	 */
	public function exists($path)
	{
		$path = rtrim($path, '/');
		$files = $this->files(dirname($path));
		return in_array($path, $files);
	}
	
	/*
	 * Uploads the local file to remote path
	 */
	public function upload($local, $remote, $mode = null)
	{
		// If the path is a directory, recursively uploads:
		// Create a dir
		// Create an array containing the files and dirs within the path
		// Upload each resource recursively begining with the last
		if(is_dir($local)) {
			$files = scandir($local);
			@$this->makeDirectory($remote);
			$files = array_filter($files, function($file) {
				return self::isChild($file);
			});
			array_map(function($file) use($remote, $local) {
				$file = $local."/".$file;
				$this->upload($file, $remote."/".basename($file));
			}, $files);
			return;
		}
		
		// Define transfer mode
		if(is_null($mode)) {
			$mode = self::transferMode($local);
		}
		
		// uploads single file
		if(!file_exists($local)) {
                    throw(new Exception("File ".$lova." not found. Unable to upload.");
		}
		ftp_put($this->resource, $remote, $local, $mode);
	}
	
	/*
	 * Downloads the remote file to local path
	 */
	public function download($remote, $local, $mode = null)
	{
		// If the path is a directory, recursively uploads:
		// Create a dir
		// Create an array containing the files and dirs within the path
		// Download each resource recursively begining with the last
		if($this->isDirectory($remote)) {
			$files = $this->files($remote);
			@mkdir($local);
			$files = array_filter($files, function($file) {
				return self::isChild($file);
			});
			array_map(function($file) use($local) {
				$this->download($file, $local."/".basename($file));
			}, $files);
			return;
		}
		
		// Define transfer mode
		if(is_null($mode)) {
			$url = $this->url().$remote;
			$mode = self::transferMode($url);
		}
		
		// downloads single file
		ftp_get($this->resource, $local, $remote, $mode);
	}
	
	/*
	 * Removes the given file or directory from FTP
	 */
	public function delete($path)
	{
		if(!$this->exists($path)) {
			throw(new Exception('Given path '.$path.' does not exist on FTP connection '.$this->connection));
		}
		if($this->isDirectory($path)) {
			$this->clean($path);
			ftp_rmdir($this->resource, $path);
			return;
		}
		ftp_delete($this->resource, $path);
	}
	
	/*
	 * Tells whether a directory is empty or not
	 */
	public function isEmpty($path) 
	{
		return count($this->files($path)) == 0;
	}
	
	/*
	 * Removes all nodes beneath the given path
	 */
	public function clean($path)
	{
		while(!$this->isEmpty($path)) {
			$files = $this->files($path);
			$this->delete(end($files));
		}
	}
	
	/*
	 * Renames the given resource
	 */
	public function move($old, $new)
	{
		ftp_rename($this->resource, $old, $new);
	}
	
	/*
	 * Returns a usable url for direct download
	 */
	public function url()
	{
		return $this->protocol.'://'.$this->username.':'.$this->password.'@'.$this->host.':'.$this->port.'/';
	}
	
	/*
	 * Copies the given resource
	 * @in old: the file to copy
	 * @in new: the location where to copy the file
	 * @buffer: the location of a local swap directory
	 */
	public function copy($old, $new, $buffer)
	{
		$this->download($old, $buffer);
		$this->upload($buffer, $new);
		self::deleteLocal($buffer);
	}
	
	/*
	 * encapsulates all vanilla php ftp function
	 */
	public function __call($method, $args) {
		$args = array_merge([$this->resource], $args);
		return call_user_func_array($method, $args);
	}
	
	/*
	 * Closes the FTP connection
	 */
	public function close()
	{
		if(!is_null($this->resource)) {
			ftp_close($this->resource);
			$this->resource = null;
		}
	}
	
	public function __destruct()
	{
		$this->close();
	}

    /*
     * Determine transfer mode for a local file
     */
	protected static function transferMode($file)
	{
		$path_parts = pathinfo($file);
        $extensionArray = [
            'am', 'asp', 'bat', 'c', 'cfm', 'cgi', 'conf',
            'cpp', 'css', 'dhtml', 'diz', 'h', 'hpp', 'htm',
            'html', 'in', 'inc', 'js', 'm4', 'mak', 'nfs',
            'nsi', 'pas', 'patch', 'php', 'php3', 'php4', 'php5',
            'phtml', 'pl', 'po', 'py', 'qmail', 'sh', 'shtml',
            'sql', 'tcl', 'tpl', 'txt', 'vbs', 'xml', 'xrc', 'csv'
        ];

		if(isset($path_parts['extension'])) {
			if(in_array(strtolower($path_parts['extension']),$extensionArray)) {
				return FTP_ASCII;
			}
			else {
				return FTP_BINARY;
			}
		}
		return FTP_BINARY;
	}
	
	/*
	 * Removes recursively folder and files within
	 */
	protected static function deleteLocal($path)
	{
		// case file
		if(is_file($path)) {
			unlink($path);
			return;
		}
		
		$files = scandir($path);
		$files = array_filter($files, function($file) {
			return self::isChild($file);
		});
		
		// recursive suppression
		array_map(function($file) use($path) {
			$file = $path."/".$file;
			self::deleteLocal($file);
		}, $files);
		
		// remove dir once its empty
		rmdir($path);
	}
	
	/*
	 * Tells whether the path is a child or current/parent dir.
	 */
	protected static function isChild($path) {
		return $path != '.' && $path != '..';
	}
}