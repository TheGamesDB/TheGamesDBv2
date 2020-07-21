<?php
require_once __DIR__ . "/../include/ErrorPage.class.php";
require_once __DIR__ . "/../include/login.common.class.php";

function returnJSONAndDie($code, $msg, $id = 0)
{
	$response =
	[
		"code" => $code,
		"msg" => $msg,
	];
	if($id != 0)
	{
		$response['id'] = $id;
	}
	echo json_encode($response);
	die();
}

$_user = phpBBuser::getInstance();
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

if(empty($_REQUEST['name']) || empty($_REQUEST['tbl']))
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}

require_once __DIR__ . "/../../include/TGDB.API.php";

$tbl_name = $_REQUEST['tbl'];
$name = $_REQUEST['name'];
try
{

	$API = TGDB::getInstance();
	if($tbl_name == 'dev' && $id = $API->InsertDev($name))
	{
		returnJSONAndDie(0, "developer Added.", $id);
	}
	else if($tbl_name == 'pub' && $id = $API->InsertPub($name))
	{
		returnJSONAndDie(0, "publisher Added.", $id);
	}
	else
	{
		returnJSONAndDie(-2, "Unexpected Error has occured, Please try again!!");
	}
}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
