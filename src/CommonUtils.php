<?php

namespace TheGamesDB;

class CommonUtils
{
	static public $WEBSITE_BASE_URL = "https://thegamesdb.net/";
	static public $API_BASE_URL = "https://api.thegamesdb.net";
	static public $BOXART_BASE_URL = "https://cdn.thegamesdb.net/images/";

	static function getImagesBaseURL()
	{
		return
		[
			"original" => CommonUtils::$BOXART_BASE_URL . "original/",
			"small" => CommonUtils::$BOXART_BASE_URL . "small/",
			"thumb" => CommonUtils::$BOXART_BASE_URL . "thumb/",
			"cropped_center_thumb" => CommonUtils::$BOXART_BASE_URL . "cropped_center_thumb/",
			"medium" => CommonUtils::$BOXART_BASE_URL . "medium/",
			"large" => CommonUtils::$BOXART_BASE_URL . "large/",
		];
	}

	static function htmlspecialchars_decodeArrayRecursive(&$array)
	{
		foreach($array as &$sub_array_item)
		{
			if(is_array($sub_array_item) || is_object($sub_array_item))
			{
				CommonUtils::htmlspecialchars_decodeArrayRecursive($sub_array_item);
			}
			else if(!is_numeric($sub_array_item) && !empty($sub_array_item))
			{
				$sub_array_item = htmlspecialchars_decode($sub_array_item);
			}
		}
	}
}
