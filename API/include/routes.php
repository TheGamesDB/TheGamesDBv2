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

function jsonSetPageUrl(&$JSON_Response, $current_page, $isNextPage)
{
    global $BASE_URL;
    $GET = $_GET;
    $JSON_Response['previous_page'] = NULL;
    $JSON_Response['next_page'] = NULL;

    if($current_page > 0)
    {
        $GET['page'] = $current_page-1;
        $JSON_Response['previous_page'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME']. "?" . http_build_query($GET,'','&');
    }

    $GET['page'] = $current_page;
    $JSON_Response['current_page'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME']. "?" . http_build_query($GET,'','&');

    if($isNextPage)
    {
        $GET['page'] = $current_page+1;
        $JSON_Response['next_page'] = "$BASE_URL/" . $_SERVER['SCRIPT_NAME']. "?" . http_build_query($GET,'','&');
    }
}

use Slim\Http\Request;
use Slim\Http\Response;

//TODO page and perhaps limit?

// Routes
$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/Games[/{GameID}]', function (Request $request, Response $response, array $args)
{
    // Sample log message
    $this->logger->info("Slim-Skeleton '/Games' route");

    $GameIDs = array();
    if(!empty($args['GameID']))
    {
        $GameIDs = $args['GameID'];
    }
    else if(!empty($_REQUEST["GameID"]))
    {
        $tmpGameIDs = explode(',', $_REQUEST["GameID"]);
        foreach($tmpGameIDs as $key => $val)
            if(is_numeric($val))
                $GameIDs[] = $val;
    }
    if(empty($GameIDs))
    {
        return $this->renderer->render($response, 'Games.api.html', $args);//TODO
    }

    $options = parseRequestOptions();
    $offset = getPage()*20;

    $API = new TGDB();
    $list = $API->GetGameByID($GameIDs, $offset, 20, $options['boxart']);
    $JSON_Response = array("count" => count($list), "games" => $list);
    return $response->write(json_encode($JSON_Response));
});

$app->get('/GamesBoxart[/{GameID}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/Game' route");
    $GameIDs = array();
    if(!empty($args['GameID']))
    {
        $GameIDs = $args['GameID'];
    }
    else if(!empty($_REQUEST["GameID"]))
    {
        $tmpGameIDs = explode(',', $_REQUEST["GameID"]);
        foreach($tmpGameIDs as $key => $val)
            if(is_numeric($val))
                $GameIDs[] = $val;
    }
    if(empty($GameIDs))
    {
        return $this->renderer->render($response, 'Games.api.html', $args);//TODO
    }

    $API = new TGDB();
    $list = $API->GetGameBoxartByID($GameIDs);
    $JSON_Response = array("count" => count($list), "gamesboxart" => $list);
    return $response->write(json_encode($JSON_Response));
    return $this->renderer->render($response, 'game.json', $args);
});

$app->get('/AllGames[/{PlatformID}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/AllGames' route");

    $PlatformIDs = array();
    if(!empty($args['PlatformID']))
    {
        $PlatformIDs = $args['PlatformID'];
    }
    else if(!empty($_REQUEST["PlatformID"]))
    {
        $tmpPlatformIDs = explode(',', $_REQUEST["PlatformID"]);
        foreach($tmpPlatformIDs as $key => $val)
            if(is_numeric($val))
                $PlatformIDs[] = $val;
    }

    $options = parseRequestOptions();
    $page = getPage();
    $offset = $page*20;

    $API = new TGDB();
    $list = $API->GetGameListByPlatform($PlatformIDs, $options, $offset);
    $JSON_Response = array(
        "count" => count($list),
        "games" => $list,
    );


     jsonSetPageUrl($JSON_Response, $page, false);

    return $response->write(json_encode($JSON_Response));
    return $this->renderer->render($response, 'game.json', $args);
});

$app->get('/PlatformsList[/]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/PlatformsList' route");

    $options = parseRequestOptions();

    $API = new TGDB();
    $list = $API->GetPlatformsList($options);
    $JSON_Response = array("count" => count($list), "platforms" => $list);
    return $response->write(json_encode($JSON_Response));
    return $this->renderer->render($response, 'game.json', $args);
});

$app->get('/Platforms[/{PlatformID}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/Platforms' route");

    $PlatformIDs = array();
    if(!empty($args['PlatformID']))
    {
        $PlatformIDs = $args['PlatformID'];
    }
    else if(!empty($_REQUEST["PlatformID"]))
    {
        $tmpPlatformIDs = explode(',', $_REQUEST["PlatformID"]);
        foreach($tmpPlatformIDs as $key => $val)
            if(is_numeric($val))
                $PlatformIDs[] = $val;
    }

    $options = parseRequestOptions();

    $API = new TGDB();
    $list = $API->GetPlatforms($PlatformIDs, $options);
    $JSON_Response = array("count" => count($list), "platforms" => $list);
    return $response->write(json_encode($JSON_Response));
    return $this->renderer->render($response, 'game.json', $args);
});

$app->group('/Search', function () {
    $this->get('/Games', function ($request, $response, $args) {


        $API = new TGDB();
        $list = $API->SearchGamesByName($_REQUEST['name']);
        $JSON_Response = array("count" => count($list), "platforms" => $list);
        return $response->write(json_encode($JSON_Response));
    });
    $this->get('/Platform', function ($request, $response, $args) {
        // Route for /users/{id:[0-9]+}/reset-password
        // Reset the password for user identified by $args['id']
    });
});