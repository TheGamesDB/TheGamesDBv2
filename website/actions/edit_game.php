<?php
require_once __DIR__ . "/../include/ErrorPage.class.php";
require_once __DIR__ . "/../include/login.phpbb.class.php";

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

$GameArrayFields = ['game_title', 'overview', 'release_date', 'players', 'coop', 'developers', 'publishers', 'youtube', 'genres', 'rating'];
if(!isset($_REQUEST['game_id']) || !is_numeric($_REQUEST['game_id']))
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}
else
{
	foreach($GameArrayFields as $field)
	{
		if(!isset($_REQUEST[$field]))
		{
			returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR . ": ($field).");
		}
		else if(empty($_REQUEST[$field]) && ($field != 'youtube' && $field != 'overview'))
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
	$filters = ['game_title' => true, 'overview' => true, 'youtube' => true, 'release_date' => true, 'players' => true, 'coop' => true, 'developers' => true, 'publishers' => true, 'genres' => true, 'rating' => true, 'alternates' => true, "serials" => true];
	$API = TGDB::getInstance();
	$old_game_data = $API->GetGameByID($_REQUEST['game_id'], 0, 1, $filters)[0];

	$res = $API->UpdateGame( $_user->GetUserID(), $_REQUEST['game_id'], $_REQUEST['game_title'], $_REQUEST['overview'], $_REQUEST['youtube'], $_REQUEST['release_date'],
		$_REQUEST['players'], $_REQUEST['coop'], $_REQUEST['developers'], $_REQUEST['publishers'], $_REQUEST['genres'], $_REQUEST['rating'],  $_REQUEST['alternate_names'],
		$_REQUEST['serials']);

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
