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

$RequiredReportArrayFields = ['game_id', 'report_type', 'metadata_0'];

foreach($RequiredReportArrayFields as $field)
{
	if(!isset($_REQUEST[$field]) || empty($_REQUEST[$field]))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR . " ($field)");
	}
}

//TODO: need a better check should we add different types
if($_REQUEST['report_type'] != 1)
{
	returnJSONAndDie(-1, ErrorPage::$MSG_INVALID_PARAM_ERROR . " (report_type)");
}

try
{

	$API = TGDB::getInstance();
	$res = $API->ReportGame($_user->GetUserID(), $_user->GetUsername(), $_REQUEST);

	switch((integer) $res)
	{
		case -2:
			$msg = "Original game does not exist.";
		break;
		case -1:
			$msg = "Reported game does not exist.";
		break;
		case 1:
			$msg = "Thank You For The Report.";
		break;
	}
	returnJSONAndDie($res, $msg . "($res)");

}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");


