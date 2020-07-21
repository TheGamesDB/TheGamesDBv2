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


$GameArrayFields = ['game_title'];
foreach($GameArrayFields as $field)
{
	if(!isset($_REQUEST[$field]) || empty($_REQUEST[$field]))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR . ": ($field).");
	}
}

require_once __DIR__ . "/../../include/TGDB.API.php";

try
{

	$API = TGDB::getInstance();
	$res = $API->GetGameCount($_REQUEST['game_title']);

	if($res > -1)
	{
		returnJSONAndDie(1, $res);
	}

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
