<?php

class CommonUtils
{
	static public $WEBSITE_BASE_URL = "https://beta.thegamesdb.net/";
	static public $API_BASE_URL = "https://api.beta.thegamesdb.net";
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
}

?>
