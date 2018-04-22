<?php
require_once __DIR__ . '/../../include/TGDB.API.php';
require_once __DIR__ . '/../../include/CommonUtils.class.php';
require_once __DIR__ . '/Utils.class.php';


use Slim\Http\Request;
use Slim\Http\Response;

$app->add(new AuthMiddleware());

// Routes
$app->get('/', function(Request $request, Response $response, array $args)
{
	$this->logger->info("TGDB '/' route");

	return $this->renderer->render($response, 'doc.html', $args);
});

$app->group('/Games', function()
{
	$this->get('/ByGameName[/{name}]', function($request, $response, $args)
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
		$offset = ($page - 1) * $limit;
		$options = Utils::parseRequestOptions();
		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->SearchGamesByName($searchTerm, $offset, $limit+1, $fields);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);

		if(count($list) > 0)
		{
			if(isset($options['boxart']) && $options['boxart'])
			{
				$IDs = array();
				foreach($list as $game)
				{
					$IDs[] = $game->id;
				}
				$JSON_Response['include']['boxart']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['boxart']['data'] = $API->GetGameBoxartByID($IDs, 0, 999, 'boxart');
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$PlatformsIDs = array();
				foreach($list as $game)
				{
					$PlatformsIDs[] = $game->Platform;
				}
				$JSON_Response['include']['Platform'] = $API->GetPlatforms($PlatformsIDs);
			}
		}

		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/ByGameID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Games/ByGameID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = ($page - 1) * $limit;
		$options = Utils::parseRequestOptions();
		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->GetGameByID($IDs, $offset, $limit+1, $fields);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);

		if(count($list) > 0)
		{
			if(isset($options['boxart']) && $options['boxart'])
			{
				$JSON_Response['include']['boxart']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['boxart']['data'] = $API->GetGameBoxartByID($IDs, 0, 999, 'boxart');
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$PlatformsIDs = array();
				foreach($list as $game)
				{
					$PlatformsIDs[] = $game->Platform;
				}
				$JSON_Response['include']['Platform']['data'] = $API->GetPlatforms($PlatformsIDs);
			}
		}

		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Games/ByPlatformID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = ($page - 1) * $limit;
		$options = Utils::parseRequestOptions();
		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->GetGameListByPlatform($IDs, $offset, $limit+1, $fields);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		if(count($list) > 0)
		{
			if(isset($options['boxart']) && $options['boxart'])
			{
				$GameIDs = array();
				foreach($list as $game)
				{
					$GameIDs[] = $game->id;
				}
				$JSON_Response['include']['boxart']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['boxart']['data'] = $API->GetGameBoxartByID($GameIDs, 0, 999, 'boxart');
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$JSON_Response['include']['Platform']['data'] = $API->GetPlatforms($IDs);
			}
		}
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/Boxart[/{GameID}]', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Games/Boxart' route");

		$GameIDs = Utils::getValidNumericFromArray($args, 'GameID');
		if(empty($GameIDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$limit = 30;
		$page = Utils::getPage();
		$offset = ($page - 1) * $limit;
		$options = Utils::parseRequestOptions();
		$filters = isset($_REQUEST['filter']) ? explode("," , $_REQUEST['filter']) : 'ALL';

		$API = TGDB::getInstance();
		$list = $API->GetGameBoxartByID($GameIDs, $offset, $limit+1, $filters);

		$count = 0;
		foreach($list as $boxarts)
		{
			$count += count($boxarts);
		}
		$has_next_page = $count > $limit;

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), 'base_url' => CommonUtils::getImagesBaseURL(), "boxart" => $list);
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
	$this->get('/Updates', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Games/Updates' route");

		if(!isset($_REQUEST['time']) && !is_numeric($_REQUEST['time']) &&  $_REQUEST['time'] < 0)
		{
			$_REQUEST['time'] = 60*24*365;
		}

		$limit = 20;
		$page = Utils::getPage();
		$offset = ($page - 1) * $limit;
		$options = Utils::parseRequestOptions();
		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->GetGamesByLatestUpdatedDate($_REQUEST['time'], $offset, $limit+1, $fields);

		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "games" => $list);
		if(count($list) > 0)
		{
			if(isset($options['boxart']) && $options['boxart'])
			{
				$GameIDs = array();
				foreach($list as $game)
				{
					$GameIDs[] = $game->id;
				}
				$JSON_Response['include']['boxart']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['boxart']['data'] = $API->GetGameBoxartByID($GameIDs, 0, 999, 'boxart');
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$PlatformsIDs = array();
				foreach($list as $game)
				{
					$PlatformsIDs[] = $game->Platform;
				}
				$JSON_Response['include']['Platform']['data'] = $API->GetPlatforms($PlatformsIDs);
			}
		}
		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
});

$app->group('/Platforms', function()
{
	$this->get('', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Platforms' route");

		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->GetPlatformsList($fields);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformID[/{id}]', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Platforms/ByPlatformID' route");

		$IDs = Utils::getValidNumericFromArray($args, 'id');
		if(empty($IDs))
		{
			$JSON_Response = Utils::getStatus(406);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->GetPlatforms($IDs, $fields);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
	$this->get('/ByPlatformName[/{name}]', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Platforms/ByPlatformName' route");

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

		$fields = Utils::parseRequestedFields();

		$API = TGDB::getInstance();
		$list = $API->SearchPlatformByName($searchTerm, $fields);

		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
		return $response->withJson($JSON_Response);
	});
});

?>
