<?php

require_once __DIR__ . "/../../include/config.class.php";
require_once __DIR__ . "/../../include/TGDB.API.php";
require_once __DIR__ . '/../../include/CommonUtils.class.php';
require_once __DIR__ . '/../../website/include/TGDBUtils.class.php';

class DiscordUtils
{
	static private function Send($embeds)
	{
		if(!isset(Config::$DiscordWebhook))
			return;

		if(!isset($embeds))
			return;

		$data = array(
			'name' => $embeds['author']['name'],
			'avatar_url' => $embeds['author']['icon_url'],
		);

		$data['embeds'] = [$embeds];
		$json_string = json_encode($data);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, Config::$DiscordWebhook);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);

		$output = curl_exec($curl);
		$output = json_decode($output, true);

		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204)
		{
			if(isset($output['message']))
			{
				throw new \Exception($output['message']);
			}
			else if(isset($output))
			{
				throw new \Exception(json_encode($output));
			}
		}

		curl_close($curl);
		return true;
	}

	static function PostGameUpdate($_user, $old_game_data, $new_game_data, $type = 0)
	{
		if(!isset($new_game_data))
			return;

		try
		{
			CommonUtils::htmlspecialchars_decodeArrayRecursive($old_game_data);
			CommonUtils::htmlspecialchars_decodeArrayRecursive($new_game_data);

			$embeds = array();
			$embeds["author"] = array(
				"name" => $_user->GetUsername(),
				"url" => "https://forums.thegamesdb.net/memberlist.php?mode=viewprofile&u=" . $_user->GetUserID(),
				"icon_url" => $_user->GetAvatar()
			);
			$embeds["title"] = $new_game_data->game_title;
			$embeds["url"] = CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$new_game_data->id";
			switch($type)
			{
				case 0:
					$embeds["color"] = 0x00b159;
					$embeds["footer"] = ['text' => "Game Added"];
					break;
				case 1:
					$embeds["color"] = 0xffc425;
					$embeds["footer"] = ['text' => "Game Updated"];
					break;
				case 2:
					$embeds["color"] = 0xd11141;
					$embeds["footer"] = ['text' => "Game Removed"];
					break;
			}

			$embeds["fields"][] = ["name" => "id", "value" => $new_game_data->id, "inline" => "true"];
			$embeds["fields"][] = ["name" => "platform", "value" => $new_game_data->platform, "inline" => "true"];
			$is_change = false;
			foreach($new_game_data as $key => $val)
			{
				if(($type == 0 || $type == 2) && ($key == "id" || $key == "platform" || $key == "game_title"))
					continue;

				if(!empty($val) && (empty($old_game_data) || empty($old_game_data->$key) || $val != $old_game_data->$key))
				{
					if($key == "uids")
					{
							$uids = [];
							foreach($val as $item)
							{
									$uids[] = $item->uid;
							}
							$val = $uids;
					}

					if(is_array($val))
						$val = implode(",", $val);
					$data = ["name" => $key, "value" => $val];
					if($key != "overview")
						$data["inline"] = "true";

						$embeds["fields"][] = $data;
					$is_change = true;
				}
				else if(empty($val) && (!empty($old_game_data) && !empty($old_game_data->$key)))
				{
					$embeds["fields"][] = ["name" => $key, "value" => "[REMOVED]", "inline" => "true"];
					$is_change = true;
				}
			}
			if(!$is_change)
				return;

			if($type == 1)
			{
				$API = TGDB::getInstance();
				$boxarts = $API->GetGameBoxartByID($new_game_data->id, 0, 9999, 'boxart');
				if(isset($boxarts[$new_game_data->id]))
				{
					$new_game_data->boxart = $boxarts[$new_game_data->id];
		
					$box_cover =  new \stdClass();
					$box_cover->front = TGDBUtils::GetAllCovers($new_game_data, 'boxart', 'front');
		
					if(!empty($box_cover->front))
					{
						$embeds["thumbnail"] = array(
							"url" => $box_cover->front[0]->thumbnail
						);
					}
				}
			}

			DiscordUtils::Send($embeds);
		}
		catch(Exception $e)
		{
			error_log($e);
		}

	}

	static function PostImageUpdate($_user, $game_id, $image_path, $type, $sub_type, $action_type)
	{
		if(!isset($game_id))
			return;

		try
		{
			$API = TGDB::getInstance();
			$game = $API->GetGameByID($game_id, 0, 1)[0];
			CommonUtils::htmlspecialchars_decodeArrayRecursive($game);

			$embeds = array();
			$embeds["author"] = array(
				"name" => $_user->GetUsername(),
				"url" => "https://forums.thegamesdb.net/memberlist.php?mode=viewprofile&u=" . $_user->GetUserID(),
				"icon_url" => $_user->GetAvatar()
			);
			$embeds["title"] = $game->game_title;
			$embeds["url"] = CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$game->id";

			switch($action_type)
			{
				case 0:
					$embeds["color"] = 0x00b159;
					$embeds["footer"] = ['text' => "Image Added"];
					break;
				case 1:
					$embeds["color"] = 0xffc425;
					$embeds["footer"] = ['text' => "Image Replaced"];
					break;
				case 2:
					$embeds["color"] = 0xd11141;
					$embeds["footer"] = ['text' => "Image Removed"];
					break;
			}
			$embeds["fields"][] = ["name" => "game_id", "value" => $game->id, "inline" => "true"];
			$embeds["fields"][] = ["name" => "type", "value" => $type, "inline" => "true"];
			if(!empty($sub_type))
			{
				$embeds["fields"][] = ["name" => "subtype", "value" => $sub_type, "inline" => "true"];
			}

			if($action_type < 2)
			{
				$embeds["image"] = array(
					"url" => $image_path
				);
			}

			DiscordUtils::Send($embeds);
		}
		catch(Exception $e)
		{
			error_log($e);
		}

	}
}
?>
