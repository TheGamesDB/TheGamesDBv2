<?php
require_once __DIR__ . '/../../include/TGDB.API.php';
require_once __DIR__ . '/Utils.class.php';


use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/', function(Request $request, Response $response, array $args)
{
	$this->logger->info("Slim-Skeleton '/' route");

	return $this->renderer->render($response, 'doc.html', $args);//TODO
});

$app->group('/Games', function()
{
	$this->get('/ByName[/{name}]', function($request, $response, $args)
	{
		if(isset($args['name']))
		{
			$searchTerm = $args['name'];
		}
		else if(isset($_REQUEST['name']))
		{
			$searchTerm = $_REQUEST['name'];
		}
		else
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = $page * $limit;
		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->SearchGamesByName($searchTerm, $offset, $limit+1, $options);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/ByGameID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Games/ByGameID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = $page * $limit;
		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->GetGameByID($IDs, $offset, $limit+1, $options);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Games/ByPlatformID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = $page * $limit;
		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->GetGameListByPlatform($IDs, $offset, $limit+1, $options);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/Boxart[/{GameID}]', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Games/Boxart' route");

		$GameIDs = Utils::getValidNumericFromArray($args, 'GameID');
		if(empty($GameIDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = $page * $limit;
		//TODO $options and $filters
		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->GetGameBoxartByID($GameIDs, $offset, $limit+1);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "boxart" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
});

$app->group('/Platforms', function()
{
	$this->get('', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Platforms' route");

		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->GetPlatformsList($options);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Platforms/ByPlatformID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = $API->GetPlatforms($IDs, $options);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformName[/{name}]', function($request, $response, $args)
	{
		$this->logger->info("Slim-Skeleton '/Platforms/ByPlatformName' route");

		if(isset($args['name']))
		{
			$searchTerm = $args['name'];
		}
		else if(isset($_REQUEST['name']))
		{
			$searchTerm = $_REQUEST['name'];
		}
		else
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$options = Utils::parseRequestOptions();

		$API = TGDB::getInstance();
		$list = array("TODO");//TODO

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
});
