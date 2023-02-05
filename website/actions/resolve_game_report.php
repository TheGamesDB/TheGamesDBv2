<?php

require __DIR__ . '/../../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\ErrorPage;

global $_user;

function returnJSONAndDie($code, $msg)
{
	echo json_encode(array("code" => $code, "msg" => $msg));
	die();
}

if(!$_user->isLoggedIn())
{
	returnJSONAndDie(-1, ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
}
else
{
	if(!$_user->hasPermission('m_delete_games'))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
	}
}

if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}

try
{

	$API = TGDB::getInstance();
	

	$res = $API->ResolveGameReport($_user->GetUserID(), $_user->GetUsername(), $_REQUEST['id']);

	returnJSONAndDie(1, "success!!");
	

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
