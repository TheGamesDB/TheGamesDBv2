<?php
require_once __DIR__ . "/../include/ErrorPage.class.php";
require_once __DIR__ . "/../include/login.common.class.php";

function returnJSONAndDie($code, $msg)
{
	echo json_encode(array("code" => $code, "msg" => $msg));
	die();
}

$_user = phpBBuser::getInstance();
if(!$_user->isLoggedIn())
{
	returnJSONAndDie(-1, ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
}
else
{
	if(!$_user->hasPermission('u_edit_games'))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
	}
}

$GameArrayFields = ['game_title', 'overview', 'release_date', 'players', 'coop', 'developers', 'publishers', 'youtube', 'genres', 'rating', 'region_id', 'country_id'];
$OptionalFields = ['youtube', 'overview', 'country_id'];
if(!isset($_REQUEST['game_id']) || !is_numeric($_REQUEST['game_id']))
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}
else
{
	foreach($GameArrayFields as $field)
	{
		if(!isset($_REQUEST[$field]) && !in_array($field, $OptionalFields))
		{
			returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR . ": ($field).");
		}
		else if(empty($_REQUEST[$field]) && !in_array($field, $OptionalFields))
		{
			returnJSONAndDie(-1, "field is empty: ($field).");
		}
		else if(($field == 'developers' || $field == 'publishers') && (empty($_REQUEST[$field]) || count($_REQUEST[$field]) < 1 || empty($_REQUEST[$field][0])))
		{
			//returnJSONAndDie(-1, "developers field is empty, if developer is not listed, please request it on the forum.");
		}
	}

	$date = explode('-', $_REQUEST['release_date']);
	if(!checkdate($date[1], $date[2], $date[0]))
	{
		returnJSONAndDie(-1, "Invalid Date Format");
	}
}

require_once __DIR__ . "/../../include/TGDB.API.php";
require_once __DIR__ . "/../include/DiscordUtils.class.php";

try
{
	$filters = ['game_title' => true, 'overview' => true, 'platform' => true, 'youtube' => true, 'release_date' => true, 'players' => true, 'coop' => true, 'developers' => true, 'publishers' => true, 'genres' => true, 'rating' => true, 'alternates' => true, "uids" => true, "region_id" => true, 'country_id' => true];
	$API = TGDB::getInstance();
	$old_game_data = $API->GetGameByID($_REQUEST['game_id'], 0, 1, $filters)[0];

	if(!empty($_REQUEST['uids']) && !empty($_REQUEST['uids'][0]))
	{
		$patterns = $API->GetUIDPattern($old_game_data->platform);
		if(empty($patterns))
		{
			returnJSONAndDie(-3, "No format found for title id, please contact us on the forum or discord to enable UID addition for this platform.");
		}
		else
		{
			$_REQUEST["uids"] = array_filter($_REQUEST["uids"]);
			foreach($_REQUEST["uids"] as $uid)
			{
				$matches = [];
				$matched = false;
				foreach($patterns as $pattern)
				{
					$regex_pat = $pattern->regex_pattern;
					if(preg_match_all("/$regex_pat/", $uid, $matches))
					{
						if(count($matches[0]) == 1 && $matches[0][0] == $uid)
						{
							$matched = true;
							break;
						}
					}
				}
				if(!$matched)
				{
					returnJSONAndDie(-2, "The UID format you're using is invalid, please contact us on the forum or discord if you please there is a mistake");
				}
			}
		}
	}

	$Lock = $API->GetGameLockByID($_REQUEST['game_id']);
	if(!$_user->hasPermission('m_delete_games'))
	{
		$_REQUEST['platform'] = $old_game_data->platform;
		foreach($Lock->iterator() as $key => $val)
		{
			if($val)
			{
				if(in_array($key, ['game_title', 'overview', 'youtube']))
				{
					$_REQUEST[$key] = htmlspecialchars_decode($old_game_data->$key);
					continue;
				}
				$_REQUEST[$key] = $old_game_data->$key;
			}
		}
	}
	else
	{
		foreach($Lock->iterator() as $key => $val)
		{
			$locked = isset($_REQUEST[$key . "_lock"]) && $_REQUEST[$key . "_lock"] == "on";
			$Lock->updateLock($key, $locked);
		}
		$Lock->commit();
	}

	$res = $API->UpdateGame( $_user->GetUserID(), $_REQUEST['game_id'], $_REQUEST['game_title'], $_REQUEST['overview'], $_REQUEST['youtube'], $_REQUEST['release_date'],
		$_REQUEST['players'], $_REQUEST['coop'], $_REQUEST['developers'], $_REQUEST['publishers'], $_REQUEST['genres'], $_REQUEST['rating'],  $_REQUEST['alternate_names'],
		$_REQUEST['uids'], $_REQUEST['platform'], $_REQUEST['region_id'], $_REQUEST['country_id']);


	if($res)
	{
		$new_game_data = $API->GetGameByID($_REQUEST['game_id'], 0, 1, $filters)[0];
		DiscordUtils::PostGameUpdate($_user, $old_game_data, $new_game_data, 1);
		returnJSONAndDie(1, "success!!");
	}

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
