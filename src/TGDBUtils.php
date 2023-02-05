<?php

namespace TheGamesDB;

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
					if(isset($art->side) && $art->side == $side)
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
				return CommonUtils::$BOXART_BASE_URL . "$return_size/" . $ret->filename;
			}
		}
		if($return_placeholder)
		{
			if(isset($game->game_title))
			{
				return "https://via.placeholder.com/200x200?text=" . urlencode($game->game_title);
			}
			elseif(isset($game->name))
			{
				return "https://via.placeholder.com/200x200?text=" . urlencode($game->name);
			}
			return "https://via.placeholder.com/200x200";
		}
	}

	public static function GetAllCovers($game, $type = '', $side = '')
	{
		$ret = array();
		$BASE_URL = CommonUtils::getImagesBaseURL();
		if(isset($game->boxart))
		{
			foreach($game->boxart as $art)
			{
				if($art->type == $type)
				{
					if($art->side == $side)
					{
						$art->thumbnail = new \stdClass();
						$art->original = $BASE_URL["original"] . $art->filename;
						$art->small = $BASE_URL["small"] . $art->filename;
						$art->cropped_center_thumb = $BASE_URL["cropped_center_thumb"] . $art->filename;
						$art->thumbnail = $BASE_URL["thumb"] . $art->filename;
						$art->medium = $BASE_URL["medium"] . $art->filename;
						$art->large = $BASE_URL["large"] . $art->filename;
						$ret[] = $art;
					}
				}
			}
		}
		return $ret;
	}

	public static function GetPlaceholderImage($Name, $size)
	{
		return "https://via.placeholder.com/200x200?text=" . urlencode($Name);
	}
}
