<?php

/**
 * Simple key-value based cache system, with file storage and reusing Wordpress options table.
 * Doesn't needs modifications of the WP Core, but it does need code changes in your theme(s)
 * @author Kartones <admin@kartones.net>
 */
class Kache
{
	// Use this to fully disable the cache
	const CACHE_ENABLED = true;

	/*
	 * 0: Disabled/log nothing
	 * 1: Log only set and invalidate calls
	 * 2: Log everything
	 */
	const LOGGING_LEVEL = 0;

	// Key constants/names
	// NOTE: Careful with the names, invalidation process does a WHERE name LIKE 'cachekey%'
	const CACHE_KEY_SIDEBAR = 'cache-sidebar';
	const CACHE_KEY_PAGES = 'cache-pages';
	const CACHE_KEY_HOMEPAGE = 'cache-home';

	// Server subpath where to store the cached contents in physical files
	const SERVER_CACHE_SUBPATH = '\cache_files\\';

	const CACHE_KEY_GLUE = '_';

	// Lifespan of each key
	private static $keysConfig = array(
		self::CACHE_KEY_SIDEBAR => 172800,	// 2d
		self::CACHE_KEY_PAGES => 172800,	// 2d
		self::CACHE_KEY_HOMEPAGE => 172800,	// 2d
	);

	// Singleton instance
	private static $instance = null;

	/**
	* Class constructor. Made private to avoid external calls
	*/
	private function __construct()
	{
	}

	/**
	* Singleton class constructor
	* return Kache Singleton instance of the class
	*/
	public static function GetInstance()
	{
		self::Log('GetInstance()', 2);
		if (self::$instance == null)
		{
			self::Log("GetInstance() NEW INSTANCE\r\n", 2);
			self::$instance = new Kache();
		}
		return self::$instance;
	}

	/**
	* Refreshes the last time of a cache key
	* @param string $cacheKey
	* @return void
	*/
	public function Refresh($cacheKey)
	{
		if (self::CACHE_ENABLED === false)
		{
			return;
		}

		self::Log('Refresh(' . $cacheKey . ')', 2);

		update_option($cacheKey, time());
	}

	/**
	* Invalidates a cache key
	* @param string $cacheKey
	* @return void
	*/
	public function Invalidate($cacheKey)
	{
		if (self::CACHE_ENABLED === false)
		{
			return;
		}

		self::Log('Invalidate(' . $cacheKey . ')', 1);

		$this->InvalidateWPOptions($cacheKey);
	}

	/**
	* Gets the actual cached contents of a cache key
	* @param string $cacheKey
	* @param string $cacheSubKey (optional) For multiple subkeys
	* @return string/false contents of the cache entry or false if the key doesn't exists or has expired
	*/
	public function Get($cacheKey, $cacheSubKey = null)
	{
		$content = false;

		if (self::CACHE_ENABLED === false)
		{
			return false;
		}

		$fullCacheKey = $cacheKey;
		if ($cacheSubKey !== null)
		{
			$fullCacheKey .= self::CACHE_KEY_GLUE . $cacheSubKey;
		}

		self::Log('Get(' . $fullCacheKey . ')', 1);

		$lastTime = get_option($fullCacheKey);
		if ($lastTime)
		{
			$lastTime = (int) $lastTime;
			// All subkeys have same expiration time
			$expired = !(time() - $lastTime <= self::$keysConfig[$cacheKey]);

			if (!$expired)
			{
				$content = $this->GrabContents($fullCacheKey);
				self::Log('Get(' . $fullCacheKey . ') HIT', 1);
			}
		}
		return $content;
	}

	/**
	* Caches a string with a specific key
	* @param string $cacheKey
	* @param string Contents to cache
	* @param string $cacheSubKey (optional) For multiple subkeys
	* @return void
	*/
	public function Set($cacheKey, $cacheContent, $cacheSubKey = null)
	{
		if (self::CACHE_ENABLED === false)
		{
			return;
		}

		$fullCacheKey = $cacheKey;
		if ($cacheSubKey !== null)
		{
			$fullCacheKey .= self::CACHE_KEY_GLUE . $cacheSubKey;
		}

		self::Log('Set(' . $fullCacheKey . ')', 2);

		if ($this->StoreContents($fullCacheKey, $cacheContent)) {
			$this->Refresh($fullCacheKey);
		}
	}

	/**
	* Invalidates all default cache keys
	* @return void
	*/
	public function InvalidateAll()
	{
		self::Log('InvalidateAll()', 2);

		$this->Invalidate(self::CACHE_KEY_SIDEBAR);
		$this->Invalidate(self::CACHE_KEY_PAGES);
		$this->Invalidate(self::CACHE_KEY_HOMEPAGE);
	}


	/**
	 * Logs an entry in the log file if logging level is high enough to the one specified
	 *
	 * @param string $data Data to log
	 * @param integer $level Logging level. If the general level is lower than this one, won't be logged
	 * @return void
	 */
	public static function Log($data, $level = 2)
	{
		if (self::LOGGING_LEVEL >= $level)
		{
			$fileName = $_SERVER['DOCUMENT_ROOT'] . self::SERVER_CACHE_SUBPATH . 'cache.log';
			$file = fopen($fileName, 'a');
			if ($file) {
				fputs($file, '@' . date('Y-m-d H:i:s') . ': ' . $data . "\r\n");
				fclose($file);
			}
		}
	}

	/**
	* Gets the contents of a cache key reading a physical file
	* @param string $cacheKey Cache key
	* @return mixed Contents of the file that stores the cache entry data or false if file not found
	*/
	private function GrabContents($cacheKey)
	{
		$content = '';

		self::Log('GrabContents(' . $cacheKey . ')', 2);

		$fileName = $_SERVER['DOCUMENT_ROOT'] . self::SERVER_CACHE_SUBPATH . $cacheKey;
		$file = fopen($fileName, 'r');
		if ($file) {
			while (!feof($file)) {
				$content .= fgets($file, 4096);
			}
		} else {
			$content = false;
		}

		return $content;
	}

	/**
	* Stores the contents by key in a physical file
	* @param string $cacheKey Cache key to identify the contents
	* @param string $cacheContent Contents to cache
	* @return boolean true if the operation went successfully, false otherwise
	*/
	private function StoreContents($cacheKey, $cacheContent)
	{
		$resultOk = false;

		self::Log('StoreContents(' . $cacheKey . ')', 2);

		$fileName = $_SERVER['DOCUMENT_ROOT'] . self::SERVER_CACHE_SUBPATH . $cacheKey;
		$file = fopen($fileName, 'w+');
		if ($file) {
			fputs($file, $cacheContent);
			fclose($file);
			$resultOk = true;
		}

		return $resultOk;
	}

	/**
	 * Invalidates the key and subkeys specified and stored as WP options
	 * @global object $wpdb Wordpress DB object
	 * @param string $cacheKey Cache key to delete (including subkeys)
	 * @return void
	 */
	private function InvalidateWPOptions($cacheKey)
	{
		global $wpdb;

		self::Log('InvalidateWPOptions(' . $cacheKey . ')', 2);

		$wpdb->query("UPDATE $wpdb->options SET option_value=false WHERE option_name LIKE '$cacheKey%'");
	}
}


// Kache ugly functions needed for the WP hooks...
function InvalidatePagesCache()
{
	$kache = Kache::GetInstance();
	$kache->Invalidate(Kache::CACHE_KEY_PAGES);
}

function InvalidateAllKache() 
{
	$kache = Kache::GetInstance();
	$kache->InvalidateAll();
}

// Kache WP hooks
add_action ('publish_post','InvalidateAllKache');
add_action ('deleted_post','InvalidateAllKache');
add_action ('post_updated', 'InvalidateAllKache');
add_action ('comment_post','InvalidatePagesCache');
add_action ('deleted_comment','InvalidatePagesCache');

?>