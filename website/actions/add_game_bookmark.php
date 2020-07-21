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

if(
	!isset($_REQUEST['games_id']) || !is_numeric($_REQUEST['games_id'])
	|| !isset($_REQUEST['is_booked']) || !is_numeric($_REQUEST['is_booked'])
	)
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}

require_once __DIR__ . "/../../include/TGDB.API.php";
try
{
	$API = TGDB::getInstance();
	$list = $API->GetGameByID($_REQUEST['games_id'], 0, 1);
	if(empty($Game = array_shift($list)))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_REMOVED_GAME_INVALID_PARAM_ERROR);
	}

	$is_booked = $_REQUEST['is_booked'] > 0 ? 1 : 0;
	$res = $API->InsertUserGameBookmark($_user->GetUserID(), $Game, $is_booked);
	if($res)
	{
		returnJSONAndDie(0, $is_booked);
	}
}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");

?>