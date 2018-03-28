<?php
require __DIR__ . '/../../include/TGDB.API.php';
require __DIR__ . '/Utils.class.php';


use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/' route");

	return $this->renderer->render($response, 'doc.html', $args);//TODO
});

$app->get('/Games[/{GameID}]', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/Games' route");

	$GameIDs = Utils::getValidNumericFromArray($args, 'GameID');
	if(empty($GameIDs))
	{
		$JSON_Response = Utils::getStatus(406);
		return $response->withJson($JSON_Response, $JSON_Response['code']);
	}

	$limit = 20;
	$page = Utils::getPage();
	$offset = $page * $limit;
	$options = Utils::parseRequestOptions();

	$API = TGDB::getInstance();
	$list = $API->GetGameByID($GameIDs, $offset, $limit+1, $options);

	if($has_next_page = count($list) > $limit)
		unset($list[$limit]);

	$JSON_Response = Utils::getStatus(200);
	$JSON_Response['data'] = array("count" => count($list), "games" => $list);
	$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

	return $response->withJson($JSON_Response);
});

$app->get('/AllGames[/{PlatformID}]', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/AllGames' route");

	$PlatformIDs = Utils::getValidNumericFromArray($args, 'PlatformID');
	$limit = 20;
	$page = Utils::getPage();
	$offset = $page * $limit;
	$options = Utils::parseRequestOptions();

	$API = TGDB::getInstance();
	$list = $API->GetGameListByPlatform($PlatformIDs, $offset, $limit+1, $options);

	if($has_next_page = count($list) > $limit)
		unset($list[$limit]);

	$JSON_Response = Utils::getStatus(200);
	$JSON_Response['data'] = array("count" => count($list), "games" => $list);
	$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

	return $response->withJson($JSON_Response);
});

$app->get('/GamesBoxart[/{GameID}]', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/Game' route");

	$GameIDs = Utils::getValidNumericFromArray($args, 'GameID');
	if(empty($GameIDs))
	{
		$JSON_Response = Utils::getStatus(406);
		return $response->withJson($JSON_Response, $JSON_Response['code']);
	}

	$limit = 20;
	$page = Utils::getPage();
	$offset = $page * $limit;
	$options = Utils::parseRequestOptions();

	$API = TGDB::getInstance();
	$list = $API->GetGameBoxartByID($GameIDs, $offset, $limit+1);

	if($has_next_page = count($list) > $limit)
		unset($list[$limit]);

	$JSON_Response = Utils::getStatus(200);
	$JSON_Response['data'] = array("count" => count($list), "gamesboxart" => $list);
	$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

	return $response->withJson($JSON_Response);
});

//is this needed? this can be made as part of /Platforms
$app->get('/PlatformsList[/]', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/PlatformsList' route");

	$options = Utils::parseRequestOptions();

	$API = TGDB::getInstance();
	$list = $API->GetPlatformsList($options);
	$JSON_Response = array("count" => count($list), "platforms" => $list);
	return $response->withJson($JSON_Response);
});

$app->get('/Platforms[/{PlatformID}]', function (Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/Platforms' route");

	$PlatformIDs = Utils::getValidNumericFromArray($args, 'PlatformID');

	$options = Utils::parseRequestOptions();

	$API = TGDB::getInstance();
	$list = $API->GetPlatforms($PlatformIDs, $options);
	$JSON_Response = Utils::getStatus(200);
	$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
	return $response->withJson($JSON_Response);
});

$app->group('/Search', function ()
{
	$this->get('/Games', function ($request, $response, $args)
	{

		$limit = 20;
		$page = Utils::getPage();
		$offset = $page * $limit;
		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->SearchGamesByName($_REQUEST['name'], 0, $limit+1, $options);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/Platform', function ($request, $response, $args)
	{
		// ? needed ?
	});
});
