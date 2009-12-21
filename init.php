<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package		AltConstructor
 * @author		Anton <anton@altsolution.net>
 */

class Super_Cache {
	
	public static $_run_first = TRUE;
	public static $_classes = array();
	public static $_uri = '';
	public static $_cached = FALSE;
	
	public static function auto_load($class)
	{
		if (self::$_run_first)
		{
			self::$_run_first = FALSE;
			
			$file = self::$_uri.'.php';
			$dir = Kohana::$cache_dir.'/cache/';
			
			if (is_file($dir.$file))
			{
				self::$_cached = TRUE;
				require $dir.$file;
				self::$_classes[$class] = $class;
			}
			
			if (class_exists($class))
				return TRUE;
		}
		
		
		$result =  Kohana::auto_load($class);
		
		self::$_classes[$class] = $class;
		
		return $result;
	}
	
	public static function shutdown_handler()
	{
		if (self::$_cached)
			return;
	
		$files = array();
		foreach (self::$_classes as $class)
		{
			$file = str_replace('_', '/', strtolower($class));
			
			if ($path = Kohana::find_file('classes', $file))
				$files[] = file_get_contents($path).'?>';
		}
		
		$file = self::$_uri.'.php';
		$dir = Kohana::$cache_dir.'/cache/';
		
		if ( ! is_dir($dir))
			mkdir($dir, 0777);
		
		file_put_contents($dir.$file, implode('', $files));
	}
}

Super_Cache::$_uri = trim(str_replace('/', '_', $_SERVER['REQUEST_URI']), '_');

spl_autoload_unregister(array('Kohana', 'auto_load'));
spl_autoload_register(array('Super_Cache', 'auto_load'));
register_shutdown_function(array('Super_Cache', 'shutdown_handler'));
