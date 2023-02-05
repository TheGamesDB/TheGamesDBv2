<?php

require __DIR__ . '/../../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Database;
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

if(
    !isset($_REQUEST['keep']) || !is_numeric($_REQUEST['keep']) ||
    !isset($_REQUEST['remove']) || !is_numeric($_REQUEST['remove'])
)
{
	returnJSONAndDie(-1, ErrorPage::$MSG_MISSING_PARAM_ERROR);
}

if($_REQUEST['remove'] == $_REQUEST['keep'])
{
	returnJSONAndDie(-1, "Invalid Selection");
}


$database = Database::getInstance();
$API = TGDB::getInstance();

$dbh = $database->dbh;

$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


$old_id = $_REQUEST['remove'];
$new_id = $_REQUEST['keep'];
$is_pub = $_REQUEST['tbl'] == 'publishers';

if($is_pub)
{
    $games_tbl = "games_pubs";
    $list_tbl = "pubs_list";
    $field = "pub_id";
    $edit_name = "publishers";
    if(count($API->GetPubsListByIDs([$old_id, $new_id])) != 2)
    {
        returnJSONAndDie(-2, "1 or both $edit_name can't be found.");
    }
}
else
{
    $games_tbl = "games_devs";
    $list_tbl = "devs_list";
    $field = "dev_id";
    $edit_name = "developers";
    if(count($API->GetDevsListByIDs([$old_id, $new_id])) != 2)
    {
        returnJSONAndDie(-2, "1 or both $edit_name can't be found.");
    }
}

try
{
    // 1) find all games with pub/dev id
    $sth = $dbh->prepare("SELECT games_id from $games_tbl where $field = :$field;");
    $sth->bindValue(":$field", $old_id);
    if($sth->execute())
    {
        $Games = $sth->fetchAll(PDO::FETCH_OBJ);
    }
    if(empty($Games))
    {
        returnJSONAndDie(-3, "Error No Games found under $edit_name");
    }

    $dbh->beginTransaction();
    // 2) delete games dev/pub
    $sth = $dbh->prepare("DELETE FROM $games_tbl where $field = :$field;");
    $sth->bindValue(":$field", $old_id);
    $sth->execute();

    // 3) delete pub/dev listing
    $sth = $dbh->prepare("DELETE FROM $list_tbl where id = :$field;");
    $sth->bindValue(":$field", $old_id);
    $sth->execute();

    // 4) updating all games listing
    $insert_values = '';
    foreach($Games as $Game)
    {
        if(!empty($insert_values))
        {
            $insert_values .= ",";
        }
    
        $insert_values .= "(" . $Game->games_id . ", $new_id) ";
    }

    $sth = $dbh->prepare("INSERT INTO  $games_tbl (games_id, $field) values $insert_values;");
    $sth->execute();
    
    if(!$dbh->commit())
    {
        $dbh->rollBack();
        echo "fail!!!";
        echo "INSERT INTO  $games_tbl (games_id, $field) values $insert_values;";
        die();
    }
}
catch(Exception $e)
{
    $dbh->rollBack();
    echo 'Message: ' .$e->getMessage();
    echo "INSERT INTO  $games_tbl (games_id, $field) values $insert_values;";
    die();
}

foreach($Games as $Game)
{
    $ids[] = $Game->games_id;
}

if($is_pub)
{
    $ids = $API->GetGamesPubs($ids);
}
else
{
    $ids = $API->GetGamesDevs($ids);
}

ob_start();
foreach($Games as $Game)
{
    $valid_ids = [];
    if(isset($ids[$Game->games_id]))
    {
        foreach($ids[$Game->games_id] as $subval)
        {
            $valid_ids[] = $subval;
        }
        if($API->InsertUserEdits(48, $Game->games_id, $edit_name, json_encode($valid_ids, JSON_NUMERIC_CHECK)))
        {
            echo "game_id: $Game->games_id\n";
            print_r($valid_ids);
            echo "Phase 2 success!!!\n";
        }
    }
}
$output = ob_get_clean();
returnJSONAndDie(0, $output);

