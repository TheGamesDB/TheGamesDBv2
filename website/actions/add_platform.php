<?php

require __DIR__ . '/../../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\WebUtils;
use TheGamesDB\ErrorPage;

global $_user;

function save_image($original_image, $dest_image, $type, $width = 0, $height = 0)
{
	if(file_exists($original_image))
	{
		try
		{
			if(!file_exists(dirname($dest_image)))
			{
				mkdir(dirname($dest_image), 0755, true);
			}
			$image = new \claviska\SimpleImage();
			$image = $image->fromFile($original_image);
			if($width != 0 && $height != 0)
			{
				if($image->getHeight() != $height  || $image->getWidth() != $width)
				{
					return "incorrect dimentions (" . $image->getHeight() . "x". $image->getWidth() .  ")";
				}

			}
			$type = ($type == 'jpg') ? 'jpeg' : $type;
			$image->toFile($dest_image, "image/$type", 100);
			unlink($original_image);
			return true;
		}
		catch(Exception $err)
		{
			error_log($err);
			return $err;
		}
	}
}

function returnJSONAndDie($code, $msg, $id = 0, $name = null)
{
	$response =
	[
		"code" => $code,
		"msg" => $msg,
	];
	if($id != 0)
	{
		$response['id'] = $id;
		$response['name'] = $name;
	}
	echo json_encode($response);
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

if(empty($_REQUEST['name']))
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}


$name = $_REQUEST['name'];
try
{
	$API = TGDB::getInstance();
	if($id = $API->InsertPlatform($_REQUEST['name'], $_REQUEST['developer'], $_REQUEST['manufacturer'], $_REQUEST['media'], $_REQUEST['cpu'], $_REQUEST['memory'], $_REQUEST['graphics'], $_REQUEST['sound'], $_REQUEST['maxcontrollers'], $_REQUEST['display'], $_REQUEST['overview'], $_REQUEST['youtube']))
	{
		$PATHS = [
			'icon' => ["path" => "/consoles/png48/", "type" => "png", "width" => 48, "height" => 48],
			'boxart'=> ["path" => "/platform/boxart/", "type" => "jpg", "width" => 0, "height" => 0]
		];
		$tmp_path = __DIR__ . "/../../cdn/images/tmp/original";
		$org_path = __DIR__ . "/../../cdn/images/original";
		foreach(['icon', 'boxart'] as $type)
		{
			if(!file_exists($tmp_path . $PATHS[$type]['path']))
			{
				mkdir($tmp_path . $PATHS[$type]['path'], 0755, true);
			}
		}
		$error_msg = "";
		foreach(['icon', 'boxart'] as $type)
		{
			if(isset($_FILES[$type]))
			{
				$uploader = new UploadHandler();

				$uploader->allowedExtensions = array('jpe', 'jpg', 'jpeg', 'gif', 'png', 'bmp');
				$uploader->sizeLimit = WebUtils::$_image_upload_size_limit;

				$uploader->inputName = $type;

				$result = $uploader->handleUpload($tmp_path . $PATHS[$type]['path'], "$id." . $PATHS[$type]['type']);
				$result["uploadName"] = $uploader->getUploadName();

				if(isset($result['success']))
				{
					$res = save_image($tmp_path . $PATHS[$type]['path'] . "$id." . $PATHS[$type]['type'], $org_path . $PATHS[$type]['path'] . "$id." . $PATHS[$type]['type'], $PATHS[$type]['type'], $PATHS[$type]['width'], $PATHS[$type]['height']);
					if($res === true)
					{
						$API->InsertPlatformImage($_user->GetUserID(), $id, $type, $PATHS[$type]['path'] . "$id." . $PATHS[$type]['type']);
					}
					else
					{
						$error_msg .= "Failed saving $type, $res\n";
					}
				}
			}
		}
		returnJSONAndDie(0, "Platform Added." . $error_msg, $id, $name);
	}
	else
	{
		returnJSONAndDie(-2, "Unexpected Error has occured, Please try again!!");
	}
}
catch (Exception $e)
{
	error_log($e);
	returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!\n$e");

}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
