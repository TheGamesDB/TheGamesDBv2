<?php
require __DIR__ . '/../../include/TGDB.API.php';
$BASE_URL = "https://api.thegamesdb.net";

function getPage()
{
    if(!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0)
    {
        return $_REQUEST['page'];
    }
    return 0;
}

function parseRequestOptions()
{
    $options = array();
    if(!empty($_REQUEST['include']))
    {
        $params = explode(',', $_REQUEST['include']);
        foreach($params as $key => $val)
        {
            $options[$val] = true;
        }
    }
    return $options;
}

function getJsonPageUrl($current_page, $has_next_page)
{
    global $BASE_URL;
    $GET = $_GET;
    $ret['previous'] = NULL;
    if($current_page > 0)
    {
        $GET['page'] = $current_page-1;
        $ret['previous'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME']. $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');
    }

    $GET['page'] = $current_page;
    $ret['current'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME']. $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');

    $ret['next'] = NULL;
    if($has_next_page)
    {
        $GET['page'] = $current_page+1;
        $ret['next'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'] . "?" . http_build_query($GET,'','&');
    }
    return $ret;
}

function getValidNumericFromArray(array $args, $index)
{
    $IDs = array();
    if(!empty($args[$index]) && is_numeric($args[$index]))
    {
        $IDs = $args[$index];
    }
    else if(!empty($_REQUEST[$index]))
    {
        $tmpIDs = explode(',', $_REQUEST[$index]);
        foreach($tmpIDs as $key => $val)
            if(is_numeric($val))
                $IDs[] = $val;
    }
    return $IDs;
}

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/' route");

    return $this->renderer->render($response, 'index.phtml', $args);//TODO
});

$app->get('/Games[/{GameID}]', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/Games' route");

    $GameIDs = getValidNumericFromArray($args, 'GameID');
    if(empty($GameIDs))
    {
        $JSON_Response = array("status" => 406, "msg" => "Invalid request: Invalid or missing paramaters.");
        return $response->withJson($JSON_Response, 406);
    }

    $limit = 20;
    $page = getPage();
    $offset = $page * $limit;
    $options = parseRequestOptions();

    $API = TGDB::getInstance();
    $list = $API->GetGameByID($GameIDs, $offset, $limit+1, $options);

    if($has_next_page = count($list) > $limit)
        unset($list[$limit]);

    $JSON_Response = array("count" => count($list), "games" => $list);
    $JSON_Response['pages'] = getJsonPageUrl($page, $has_next_page);

    return $response->withJson($JSON_Response);
});

$app->get('/AllGames[/{PlatformID}]', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/AllGames' route");

    $PlatformIDs = getValidNumericFromArray($args, 'PlatformID');
    $limit = 20;
    $page = getPage();
    $offset = $page * $limit;
    $options = parseRequestOptions();

    $API = TGDB::getInstance();
    $list = $API->GetGameListByPlatform($PlatformIDs, $offset, $limit+1, $options);

    if($has_next_page = count($list) > $limit)
        unset($list[$limit]);

    $JSON_Response = array("count" => count($list), "games" => $list);
    $JSON_Response['pages'] = getJsonPageUrl($page, $has_next_page);

    return $response->withJson($JSON_Response);
});

$app->get('/GamesBoxart[/{GameID}]', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/Game' route");

    $GameIDs = getValidNumericFromArray($args, 'GameID');
    if(empty($GameIDs))
    {
        $JSON_Response = array("status" => 406, "msg" => "Invalid request: Invalid or missing paramaters.");
        return $response->withJson($JSON_Response, 406);
    }

    $limit = 20;
    $page = getPage();
    $offset = $page * $limit;
    $options = parseRequestOptions();

    $API = TGDB::getInstance();
    $list = $API->GetGameBoxartByID($GameIDs, $offset, $limit+1);

    if($has_next_page = count($list) > $limit)
        unset($list[$limit]);

    $JSON_Response = array("count" => count($list), "gamesboxart" => $list);
    $JSON_Response['pages'] = getJsonPageUrl($page, $has_next_page);

    return $response->withJson($JSON_Response);
});

//is this needed? this can be made as part of /Platforms
$app->get('/PlatformsList[/]', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/PlatformsList' route");

    $options = parseRequestOptions();

    $API = TGDB::getInstance();
    $list = $API->GetPlatformsList($options);
    $JSON_Response = array("count" => count($list), "platforms" => $list);
    return $response->withJson($JSON_Response);
});

$app->get('/Platforms[/{PlatformID}]', function (Request $request, Response $response, array $args)
{
    $this->logger->info("Slim-Skeleton '/Platforms' route");

    $PlatformIDs = getValidNumericFromArray($args, 'PlatformID');

    $options = parseRequestOptions();

    $API = TGDB::getInstance();
    $list = $API->GetPlatforms($PlatformIDs, $options);
    $JSON_Response = array("count" => count($list), "platforms" => $list);
    return $response->withJson($JSON_Response);
});

$app->group('/Search', function ()
{
    $this->get('/Games', function ($request, $response, $args)
    {

        $limit = 20;
        $page = getPage();
        $offset = $page * $limit;
        $options = parseRequestOptions();

        $API = TGDB::getInstance();
        $list = $API->SearchGamesByName($_REQUEST['name'], 0, $limit+1, $options);

        if($has_next_page = count($list) > $limit)
            unset($list[$limit]);

        $JSON_Response = array("count" => count($list), "platforms" => $list);
        $JSON_Response['pages'] = getJsonPageUrl($page, $has_next_page);

        return $response->withJson($JSON_Response);
    });
    $this->get('/Platform', function ($request, $response, $args)
    {
        // ? needed ?
    });
});
