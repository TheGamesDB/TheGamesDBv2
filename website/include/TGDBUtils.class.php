<?php
require_once __DIR__ . "/../../API/include/Utils.class.php";

class TGDBUtils
{
	public static function GetCover($game, $type = '', $side = '', $return_lazy_results = false, $return_placeholder = true, $return_size = 'thumb')
	{
		if(isset($game->boxart))
		{
			foreach($game->boxart as $art)
			{
				if($return_lazy_results && !isset($ret))
				{
					$ret = $art;
				}

				if($art->type == $type)
				{
					if($art->side == $side)
					{
						$ret = $art;
						break;
					}
					else if($return_lazy_results)
					{
						$ret = $art;
					}
				}
			}
			if(isset($ret))
			{
				return Utils::$BOXART_BASE_URL . "$return_size/" . $ret->filename;
			}
		}
		if($return_placeholder)
		{
			if(isset($game->GameTitle))
			{
				return "https://via.placeholder.com/200x250?text=$game->GameTitle";
			}
			elseif(isset($game->name))
			{
				return "https://via.placeholder.com/200x250?text=$game->name";
			}
			return "https://via.placeholder.com/200x250";
		}
	}

	public static function GetAllCovers($game, $type = '', $side = '')
	{
		$ret = array();
		if(isset($game->boxart))
		{
			foreach($game->boxart as $art)
			{
				if($art->type == $type)
				{
					if($art->side == $side)
					{
						$art->thumbnail = new \stdClass();
						$art->original = Utils::$BOXART_BASE_URL . "original/" . $art->filename;
						$art->small = Utils::$BOXART_BASE_URL . "small/" . $art->filename;
						$art->cropped_center_thumb = Utils::$BOXART_BASE_URL . "cropped_center_thumb/" . $art->filename;
						$art->thumbnail = Utils::$BOXART_BASE_URL . "thumb/" . $art->filename;
						$art->medium = Utils::$BOXART_BASE_URL . "medium/" . $art->filename;
						$art->large = Utils::$BOXART_BASE_URL . "large/" . $art->filename;
						$ret[] = $art;
					}
				}
			}
		}
		return $ret;
	}

	public static function GetPlaceholderImage($Name, $size)
	{
		return "https://via.placeholder.com/200x250?text=$Name";
	}
}

?>