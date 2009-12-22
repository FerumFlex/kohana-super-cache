<?php defined('SYSPATH') or die('No direct script access.');

class Super_Cache {
	
	protected $_run_first = TRUE;
	protected $_classes = array();
	protected $_cached = FALSE;
	
	protected $_directory = '';
	protected $_filename = '';
	
	public $lifetime;
	
	public function __construct($lifetime = 3600)
	{
		if (Kohana::$caching)
		{
			$this->lifetime = $lifetime;
		
			$request = Request::instance();
			$uri = $request->directory.'.'.$request->controller.'.'.$request->action;
			$name = $uri.'.php';
			
			$this->_filename = sha1($name).'.php';
			// Cache directories are split by keys to prevent filesystem overload
			$this->_directory = Kohana::$cache_dir.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$this->_filename[0].$this->_filename[1].DIRECTORY_SEPARATOR;
		
			spl_autoload_unregister(array('Kohana', 'auto_load'));
			spl_autoload_register(array($this, 'auto_load'));
			register_shutdown_function(array($this, 'shutdown_handler'));
		}
	}
	
	public function auto_load($class)
	{
		if ($this->_run_first)
		{
			$this->_run_first = FALSE;
			
			if (is_file($this->_directory.$this->_filename))
			{
				
				if ((time() - filemtime($this->_directory.$this->_filename)) < $this->lifetime)
				{
					$this->_cached = TRUE;
					require $this->_directory.$this->_filename;
					$this->_classes[$class] = $class;
					
					if (class_exists($class))
						return TRUE;
				} else {
					// Cache has expired
					unlink($this->_directory.$this->_filename);
				}
			}
		}
		
		$result =  Kohana::auto_load($class);
		
		$this->_classes[$class] = $class;
		
		return $result;
	}
	
	public function shutdown_handler()
	{
		if ($this->_cached)
			return;
		
		$files = array();
		foreach ($this->_classes as $class)
		{
			$file = str_replace('_', '/', strtolower($class));
			
			if ($path = Kohana::find_file('classes', $file))
				$files[] = file_get_contents($path).'?>';
		}
		
		if ( ! is_dir($this->_directory))
			mkdir($this->_directory, 0777, TRUE);
		
		file_put_contents($this->_directory.$this->_filename, implode('', $files));
	}
}
