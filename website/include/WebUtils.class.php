<?php

class WebUtils
{

	public static $_image_upload_count_limit = 20;
	public static $_image_upload_size_limit = 18 * 1024 *1024;

	static function truncate($text, $maxlen, $add_leading_trail = false)
	{
		if (strlen($text) < $maxlen)
		{
			return $text;
		}
		$pos = strpos($text, " ", $maxlen - 5);
		if ($pos !== false && !($add_leading_trail && $pos + 3 >= strlen($text)))
		{
			$ret = substr($text, 0, $pos);
			if ($add_leading_trail)
			{
				$ret .= "...";
			}
			return $ret;
		}
		return $text;
	}

	static function purgeCDNCacheFile($filenames)
	{
		require_once __DIR__ . '/../../vendor/autoload.php';
		require_once __DIR__ . '/../../include/config.class.php';
		if(!isset(Config::$_CF_EMAIL))
			return false;

		try
		{
			$key = new \Cloudflare\API\Auth\APIKey(Config::$_CF_EMAIL, Config::$_CF_KEY);
			$adapter = new Cloudflare\API\Adapter\Guzzle($key);

			$zones = new \Cloudflare\API\Endpoints\Zones($adapter);

			foreach($filenames as $filename)
			{
				$files[] = "https://cdn.thegamesdb.net/$filename";
			}
			return $zones->cachePurge(Config::$_CF_ZONE_ID, $files);
		}
		catch (Exception $e)
		{
			error_log($err);
		}
		return false;
	}

	static function purgeCDNCache($img_name)
	{
		require_once __DIR__ . '/../../vendor/autoload.php';
		require_once __DIR__ . '/../../include/config.class.php';
		if(!isset(Config::$_CF_EMAIL))
			return false;

		try
		{
			$key = new \Cloudflare\API\Auth\APIKey(Config::$_CF_EMAIL, Config::$_CF_KEY);
			$adapter = new Cloudflare\API\Adapter\Guzzle($key);

			$zones = new \Cloudflare\API\Endpoints\Zones($adapter);
			$sizes = array("small", "thumb", "original", "cropped_center_thumb", "cropped_center_thumb_square", "medium", "large");

			foreach($sizes as $size)
			{
				$files[] = "https://cdn.thegamesdb.net/images/$size/$img_name";
			}
			return $zones->cachePurge(Config::$_CF_ZONE_ID, $files);
		}
		catch (Exception $e)
		{
			error_log($err);
		}
		return false;
	}

	static function purgeCDNCacheArray($img_names)
	{
		require_once __DIR__ . '/../../vendor/autoload.php';
		require_once __DIR__ . '/../../include/config.class.php';
		if(!isset(Config::$_CF_EMAIL))
			return;

		$key = new \Cloudflare\API\Auth\APIKey(Config::$_CF_EMAIL, Config::$_CF_KEY);
		$adapter = new Cloudflare\API\Adapter\Guzzle($key);

		$zones = new \Cloudflare\API\Endpoints\Zones($adapter);
		foreach ($img_names as $value)
		{
			$sizes = array("small", "thumb", "original", "cropped_center_thumb", "cropped_center_thumb_square", "medium", "large");

			foreach($sizes as $size)
			{
				$files[] = "https://cdn.thegamesdb.net/images/$size/$value";
			}
		}
		$i = 0;
		$arrycount = 0;
		foreach (array_reverse(array_chunk($files, 499)) as $files_chunk)
		{
			$i++;
			$arrycount++;
			 $zones->cachePurge(Config::$_CF_ZONE_ID, $files_chunk);
			 if($i > 1100)
			 {
				$i = 0;
				sleep(300);
			 }
			 echo "$arrycount\n";

		}
	}
}

?>