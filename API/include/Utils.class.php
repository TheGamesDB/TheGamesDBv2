<?php

class Utils
{
	static private $BASE_URL = "https://api.thegamesdb.net";

	static private $_statusMSG = array(
		200 => "Success",
		401 => "This route requires and API key and no API key was provided.",
		403 => "Invalid API key was provided.",
		406 => "Invalid request: Invalid or missing paramaters.",
	);

	function getStatus($code)
	{
		if(isset(Utils::$_statusMSG[$code]))
		{
			$statusMSG = Utils::$_statusMSG[$code];
		}
		else
		{
			$statusMSG = "Unknown Error Code";
		}
		return array("code" => $code, "status" => $statusMSG);
	}

	static function getPage()
	{
		if(!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0)
		{
			return $_REQUEST['page'];
		}
		return 0;
	}
	
	static function parseRequestOptions()
	{
		$options = array();
		if(!empty($_REQUEST['include']))
		{
			$params = explode(',', $_REQUEST['include']);
			foreach($params as $key => $val)
			{
				$options[$val] = true;
			}
		}
		return $options;
	}
	
	static function getJsonPageUrl($current_page, $has_next_page)
	{
		$GET = $_GET;
		$ret['previous'] = NULL;
		if($current_page > 0)
		{
			$GET['page'] = $current_page-1;
			$ret['previous'] = Utils::$BASE_URL . "/" . $_SERVER['SCRIPT_NAME']. $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');
		}
	
		$GET['page'] = $current_page;
		$ret['current'] = Utils::$BASE_URL . "/" . $_SERVER['SCRIPT_NAME']. $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');
	
		$ret['next'] = NULL;
		if($has_next_page)
		{
			$GET['page'] = $current_page+1;
			$ret['next'] = Utils::$BASE_URL . "/" . $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');
		}
		return $ret;
	}
	
	static function getValidNumericFromArray(array $args, $index)
	{
		$IDs = array();
		if(!empty($args[$index]) && is_numeric($args[$index]))
		{
			$IDs = $args[$index];
		}
		else if(!empty($_REQUEST[$index]))
		{
			$tmpIDs = explode(',', $_REQUEST[$index]);
			foreach($tmpIDs as $key => $val)
				if(is_numeric($val))
					$IDs[] = $val;
		}
		return $IDs;
	}
}

?>