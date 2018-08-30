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


$GameArrayFields = ['game_title', 'overview', 'release_date', 'players', 'coop', 'developer', 'publisher', 'youtube'];
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
	}

	$date = explode('-', $_REQUEST['release_date']);
	if(!checkdate($date[1], $date[2], $date[0]))
	{
		returnJSONAndDie(-1, "Invalid Date Format");
	}
}

require_once __DIR__ . "/../../include/TGDB.API.php";

try
{

	$API = TGDB::getInstance();
	$res = $API->UpdateGame( $_user->GetUserID(), $_REQUEST['game_id'], $_REQUEST['game_title'], $_REQUEST['overview'], $_REQUEST['youtube'], $_REQUEST['release_date'],
		$_REQUEST['players'], $_REQUEST['coop'], $_REQUEST['Developer'], $_REQUEST['publisher']);

	if($res)
	{
		returnJSONAndDie(1, "success!!");
	}

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
