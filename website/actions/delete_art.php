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
	if(!$_user->hasPermission('m_delete_games'))
	{
		returnJSONAndDie(-1, ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
	}
}

if(
	!isset($_REQUEST['game_id']) || !is_numeric($_REQUEST['game_id'])
	|| !isset($_REQUEST['image_id']) || !is_numeric($_REQUEST['image_id'])

	)
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}

require_once __DIR__ . "/../../include/TGDB.API.php";
require_once __DIR__ . "/../include/WebUtils.class.php";
require_once __DIR__ . "/../include/DiscordUtils.class.php";

try
{

	$API = TGDB::getInstance();
	$covers = $API->GetGameBoxartByID($_REQUEST['game_id'], 0, 99, []);

	if(!empty($covers) && ($covers = $covers[$_REQUEST['game_id']]))
	{
		$sizes = ["original", "small", "thumb", "cropped_center_thumb", "cropped_center_thumb_square", "medium", "large"];
		foreach($covers as $cover)
		{
			if($cover->id == $_REQUEST['image_id'])
			{
				foreach($sizes as $size)
				{
					$image_to_delete = __DIR__ . "/../../cdn/images/$size/" . $cover->filename;
					if(file_exists($image_to_delete))
					{
						unlink($image_to_delete);
					}
				}
				WebUtils::purgeCDNCache($cover->filename);
				$res = $API->DeleteGameImages($_user->GetUserID(), $_REQUEST['game_id'], $_REQUEST['image_id'], $cover->type);
				if($res)
				{
					DiscordUtils::PostImageUpdate($_user, $_REQUEST['game_id'], '',  $cover->type, $cover->side, 2);
					returnJSONAndDie(1, "success!!");
				}
			}
		}
	}
	returnJSONAndDie(-1, "Couldnt find image to delete, please refresh page and try again.");
}
catch (Exception $e)
{
	error_log($e);
}
returnJSONAndDie(-1, "Unexpected Error has occured, Please try again!!");
