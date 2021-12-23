<?php
require_once __DIR__ . '/../../include/TGDB.API.php';
require_once __DIR__ . '/../../include/CommonUtils.class.php';
require_once __DIR__ . '/Utils.class.php';
require_once __DIR__ . '/APIAccessDB.class.php';

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/', function(Request $request, Response $response, array $args)
{
	$this->logger->info("TGDB '/' route");

	return $this->renderer->render($response, 'doc.html', $args);
});

$app->group('/v1.1/Games', function()
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
		$natural_search = isset($_REQUEST['mode']) && $_REQUEST['mode'] = "natural";
		$filter_search = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : [];

		$API = TGDB::getInstance();

		if($natural_search)
		{
			$list = $API->SearchGamesByNameFilter_Natural($searchTerm, $filter_search, $offset, $limit + 1, $fields);
		}
		else
		{
			$list = $API->SearchGamesByNameFilter($searchTerm, $filter_search, $offset, $limit + 1, $fields);
		}


		if($has_next_page = count($list) > $limit)
			unset($list[$limit]);

		CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
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
			if(isset($options['platform']) && $options['platform'])
			{
				$PlatformsIDs = array();
				foreach($list as $game)
				{
					$PlatformsIDs[] = $game->platform;
				}
				$JSON_Response['include']['platform']['data'] = $API->GetPlatforms($PlatformsIDs);
			}
		}

		$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

		return $response->withJson($JSON_Response);
	});
})->add(new AuthMiddleware());

$app->group('/v1', function()
{
	$this->group('/Games', function()
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
			$natural_search = isset($_REQUEST['mode']) && $_REQUEST['mode'] == "natural";
			$filter_search = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : [];

			$API = TGDB::getInstance();

			if($natural_search)
			{
				$list = $API->SearchGamesByNameFilter_Natural($searchTerm, $filter_search, $offset, $limit + 1, $fields);
			}
			else
			{
				$list = $API->SearchGamesByNameFilter($searchTerm, $filter_search, $offset, $limit + 1, $fields);
			}

			if($has_next_page = count($list) > $limit)
				unset($list[$limit]);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
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
				if(isset($options['platform']) && $options['platform'])
				{
					$PlatformsIDs = array();
					foreach($list as $game)
					{
						$PlatformsIDs[] = $game->platform;
					}
					$JSON_Response['include']['platform'] = $API->GetPlatforms($PlatformsIDs);
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

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "games" => $list);

			if(count($list) > 0)
			{
				if(isset($options['boxart']) && $options['boxart'])
				{
					$JSON_Response['include']['boxart']['base_url'] = CommonUtils::getImagesBaseURL();
					$JSON_Response['include']['boxart']['data'] = $API->GetGameBoxartByID($IDs, 0, 999, 'boxart');
				}
				if(isset($options['platform']) && $options['platform'])
				{
					$PlatformsIDs = array();
					foreach($list as $game)
					{
						$PlatformsIDs[] = $game->platform;
					}
					$JSON_Response['include']['platform']['data'] = $API->GetPlatforms($PlatformsIDs);
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

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
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
				if(isset($options['platform']) && $options['platform'])
				{
					$JSON_Response['include']['platform']['data'] = $API->GetPlatforms($IDs);
				}
			}
			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
		$this->get('/ByGameUniqueID[/{uid}]', function($request, $response, $args)
		{
			if(isset($args['uid']))
			{
				$UniqueIDs = $args['uid'];
			}
			else if(isset($_REQUEST['uid']))
			{
				$UniqueIDs = $_REQUEST['uid'];
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
			if(isset($_REQUEST['filter']['platform']) && (!is_array($_REQUEST['filter']['platform']) || !in_array(0, $_REQUEST['filter']['platform'])))
			{
				if(!is_array($_REQUEST['filter']['platform']))
				{
					$PlatformsIDs = explode(",", $_REQUEST['filter']['platform']);
				}
				else
				{
					$PlatformsIDs = $_REQUEST['filter']['platform'];
				}
				$list = $API->SearchGamesByUniqueIDByPlatformID($UniqueIDs, $PlatformsIDs, $offset, $limit + 1, $fields);
			}
			else
			{
				$list = $API->SearchGamesByUniqueID($UniqueIDs, $offset, $limit+1, $fields);
			}

			if($has_next_page = count($list) > $limit)
				unset($list[$limit]);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
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
				if(isset($options['platform']) && $options['platform'])
				{
					$PlatformsIDs = array();
					foreach($list as $game)
					{
						$PlatformsIDs[] = $game->platform;
					}
					$JSON_Response['include']['platform']['data'] = $API->GetPlatforms($PlatformsIDs);
				}
			}

			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
		$this->get('/ByGameHash[/{hash}]', function($request, $response, $args)
		{
			if(isset($args['hash']))
			{
				$hash = $args['hash'];
			}
			else if(isset($_REQUEST['hash']))
			{
				$hash = $_REQUEST['hash'];
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
			$PlatformsIDs = '';
			$filter_type = '';
			if(isset($_REQUEST['filter']['platform']) && (!is_array($_REQUEST['filter']['platform']) || !in_array(0, $_REQUEST['filter']['platform'])))
			{
				if(!is_array($_REQUEST['filter']['platform']))
				{
					$PlatformsIDs = explode(",",$_REQUEST['filter']['platform']);
				}
				else
				{
					$PlatformsIDs = $_REQUEST['filter']['platform'];
				}
			}
			if(isset($_REQUEST['filter']['type']) && (!is_array($_REQUEST['filter']['type'])))
			{
				$filter_type = $_REQUEST['filter']['type'];
			}

			$list = $API->SearchGamesByHashByPlatformID($hash, $PlatformsIDs, $filter_type, $offset, $limit + 1, $fields);

			if($has_next_page = count($list) > $limit)
				unset($list[$limit]);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
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
				if(isset($options['platform']) && $options['platform'])
				{
					$PlatformsIDs = array();
					foreach($list as $game)
					{
						$PlatformsIDs[] = $game->platform;
					}
					$JSON_Response['include']['platform']['data'] = $API->GetPlatforms($PlatformsIDs);
				}
			}

			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
		$this->get('/Images[/{games_id}]', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Games/Images' route");

			$GameIDs = Utils::getValidNumericFromArray($args, 'games_id');
			if(empty($GameIDs))
			{
				$JSON_Response = Utils::getStatus(406);
				return $response->withJson($JSON_Response, $JSON_Response['code']);
			}

			$limit = 20;
			$page = Utils::getPage();
			$offset = ($page - 1) * $limit;
			$options = Utils::parseRequestOptions();
			$filters = isset($_REQUEST['filter']['type']) ? explode("," , $_REQUEST['filter']['type']) : 'ALL';

			$API = TGDB::getInstance();
			$list = $API->GetGameBoxartByID($GameIDs, $offset, $limit+1, $filters);

			if($has_next_page = count($list) > $limit)
			{
				$list_keys = array_keys($list);
				unset($list[end($list_keys)]);
			}
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), 'base_url' => CommonUtils::getImagesBaseURL(), "images" => $list);
			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
		$this->get('/Updates', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Games/Updates' route");

			$limit = 100;
			$page = Utils::getPage();
			$offset = ($page - 1) * $limit;

			$API = TGDB::getInstance();

			if(isset($_REQUEST['last_edit_id']) && is_numeric($_REQUEST['last_edit_id']) && ($_REQUEST['last_edit_id'] > -1))
			{
				$list = $API->GetUserEditsByID($_REQUEST['last_edit_id'], $offset, $limit+1);
			}
			else
			{
				if(!isset($_REQUEST['time']) || !is_numeric($_REQUEST['time']) ||  $_REQUEST['time'] < 0)
				{
					$_REQUEST['time'] = 60*24;
				}
				$list = $API->GetUserEditsByTime($_REQUEST['time'], $offset, $limit+1);
			}

			if($has_next_page = count($list) > $limit)
				unset($list[$limit]);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "updates" => $list);
			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
	});

	$this->group('/Platforms', function()
	{
		$this->get('', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Platforms' route");

			$fields = Utils::parseRequestedFields();
			$options = Utils::parseRequestOptions();

			$API = TGDB::getInstance();
			$list = $API->GetPlatformsList($fields);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
			if(isset($options['boxart']))
			{
				$PlatformIDs = array();
				foreach($list as &$platform)
				{
					$PlatformIDs[] = $platform->id;
				}
				$JSON_Response['include']['images']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['images']['data'] = $API->GetPlatformBoxartByID($PlatformIDs, 0, 99999, ['boxart']);
			}
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
			$options = Utils::parseRequestOptions();

			$API = TGDB::getInstance();
			$list = $API->GetPlatforms($IDs, $fields);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
			if(isset($options['boxart']))
			{
				$PlatformIDs = array();
				foreach($list as &$platform)
				{
					$PlatformIDs[] = $platform->id;
				}
				$JSON_Response['include']['images']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['images']['data'] = $API->GetPlatformBoxartByID($PlatformIDs, 0, 99999, ['boxart']);
			}
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
			$options = Utils::parseRequestOptions();

			$API = TGDB::getInstance();
			$list = $API->SearchPlatformByName($searchTerm, $fields);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "platforms" => $list);
			if(isset($options['boxart']))
			{
				$PlatformIDs = array();
				foreach($list as &$platform)
				{
					$PlatformIDs[] = $platform->id;
				}
				$JSON_Response['include']['images']['base_url'] = CommonUtils::getImagesBaseURL();
				$JSON_Response['include']['images']['data'] = $API->GetPlatformBoxartByID($PlatformIDs, 0, 99999, ['boxart']);
			}
			return $response->withJson($JSON_Response);
		});
		$this->get('/Images[/{platforms_id}]', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Platforms/images' route");

			$GameIDs = Utils::getValidNumericFromArray($args, 'platforms_id');
			if(empty($GameIDs))
			{
				$JSON_Response = Utils::getStatus(406);
				return $response->withJson($JSON_Response, $JSON_Response['code']);
			}

			$limit = 30;
			$page = Utils::getPage();
			$offset = ($page - 1) * $limit;
			$options = Utils::parseRequestOptions();
			$filters = isset($_REQUEST['filter']['type']) ? explode(",", $_REQUEST['filter']['type']) : 'ALL';

			$API = TGDB::getInstance();
			$list = $API->GetPlatformBoxartByID($GameIDs, $offset, $limit+1, $filters);

			$count = 0;
			foreach($list as $boxarts)
			{
				$count += count($boxarts);
			}
			$has_next_page = $count > $limit;

			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), 'base_url' => CommonUtils::getImagesBaseURL(), "images" => $list);
			$JSON_Response['pages'] = Utils::getJsonPageUrl($page, $has_next_page);

			return $response->withJson($JSON_Response);
		});
	});

	$this->get('/Genres', function($request, $response, $args)
	{
		$this->logger->info("TGDB '/Genres' route");
		$API = TGDB::getInstance();
		$list = $API->GetGenres();

		CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
		$JSON_Response = Utils::getStatus(200);
		$JSON_Response['data'] = array("count" => count($list), "genres" => $list);
		return $response->withJson($JSON_Response);
	});

	$this->group('/Developers', function()
	{
		$this->get('', function($request, $response, $args)
		{
			$limit = -1;
			if(!empty($_REQUEST['limit']) && is_numeric($_REQUEST['limit']) && $_REQUEST['limit'] > 0)
			{
				$limit = $_REQUEST['limit'];
			}
			$this->logger->info("TGDB '/Developers' route");
			$API = TGDB::getInstance();
			$list = $API->GetDevsList($limit);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "developers" => $list);
			return $response->withJson($JSON_Response);
		});
	});

	$this->group('/Publishers', function()
	{
		$this->get('', function($request, $response, $args)
		{
			$limit = -1;
			if(!empty($_REQUEST['limit']) && is_numeric($_REQUEST['limit']) && $_REQUEST['limit'] > 0)
			{
				$limit = $_REQUEST['limit'];
			}
			$this->logger->info("TGDB '/Publishers' route");
			$API = TGDB::getInstance();
			$list = $API->GetPubsList($limit);

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "publishers" => $list);
			return $response->withJson($JSON_Response);
		});
	});

	$this->group('/Regions', function()
	{
		$this->get('', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Regions' route");
			$API = TGDB::getInstance();
			$list = $API->GetRegionsList();

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "regions" => $list);
			return $response->withJson($JSON_Response);
		});
	});

	$this->group('/Countries', function()
	{
		$this->get('', function($request, $response, $args)
		{
			$this->logger->info("TGDB '/Countries' route");
			$API = TGDB::getInstance();
			$list = $API->GetCountriesList();

			CommonUtils::htmlspecialchars_decodeArrayRecursive($list);
			$JSON_Response = Utils::getStatus(200);
			$JSON_Response['data'] = array("count" => count($list), "countries" => $list);
			return $response->withJson($JSON_Response);
		});
	});
})->add(new AuthMiddleware());

$app->get('/v1/API/Limit', function($request, $response, $args)
{
	$this->logger->info("TGDB '/v1/API/Limit' route");
	$auth = APIAccessDB::getInstance();
	$User = $auth->GetUserAllowanceByAPIKey($_REQUEST['apikey']);
	if(empty($_REQUEST['apikey']))
	{
		$JSON_Response = Utils::getStatus(401);
		return $response->withJson($JSON_Response, $JSON_Response['code']);
	}
	else
	{
		if(!empty($User))
		{
			$monthly_allowance = (!empty($User->monthly_allowance)) ? $User->monthly_allowance : 0;
			$monthly_count = (!empty($User->count)) ? $User->count : 0;
			$refresh = strtotime("+30 days", strtotime($User->last_refresh_date)) - time();
			if($update_refresh_date = $refresh < 0)
			{
				$refresh = strtotime("+30 days", 0);
				$monthly_count = 0;
			}
			$remaining_monthly_allowance = $monthly_allowance - $monthly_count;
			if($remaining_monthly_allowance > 0 || $User->extra_allowance > 0)
			{
				$use_extra = $remaining_monthly_allowance <= 0;

				$JSON_Response = [];
				$JSON_Response['remaining_monthly_allowance'] = $remaining_monthly_allowance + (!$use_extra ? -1 : 0);
				$JSON_Response['extra_allowance'] =  $User->extra_allowance + ($use_extra ? -1 : 0);
				$JSON_Response['allowance_refresh_timer'] = ($User->is_private_key == 1) ? NULL : $refresh;
				return $response->withJson($JSON_Response, 200);
			}
			else
			{
				$JSON_Response = [];
				$JSON_Response['remaining_monthly_allowance'] = 0;
				$JSON_Response['allowance_refresh_timer'] = ($User->is_private_key == 1) ? NULL : $refresh;
				return $response->withJson($JSON_Response, 200);
			}
		}
		else
		{
			$JSON_Response = [];
			$JSON_Response['remaining_monthly_allowance'] = 0;
			$JSON_Response['allowance_refresh_timer'] = 0;
			return $response->withJson($JSON_Response, 200);
		}
	}
});

?>
