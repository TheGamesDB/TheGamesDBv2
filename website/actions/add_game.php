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


$GameArrayFields = ['GameTitle', 'Overview', 'ReleaseDateRevised', 'Players', 'coop', 'Developer', 'Publisher', 'Youtube'];
foreach($GameArrayFields as $field)
{
	if(!isset($_REQUEST[$field]))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR . ": ($field).");
	}
}

$date = explode('-', $_REQUEST['ReleaseDateRevised']);
if(!checkdate($date[1], $date[2], $date[0]))
{
	returnJSONAndDie(-1, "Invalid Date Format");
}


require_once __DIR__ . "/../../include/TGDB.API.php";

try
{

	$API = TGDB::getInstance();
	$res = $API->InsertGame($_user->GetUserID(), $_REQUEST['GameTitle'],  $_REQUEST['Overview'], $_REQUEST['Youtube'], $_REQUEST['ReleaseDateRevised'],
		$_REQUEST['Players'], $_REQUEST['coop'], $_REQUEST['Developer'], $_REQUEST['Publisher']);

	if($res)
	{
		returnJSONAndDie(1, $res);
	}

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
