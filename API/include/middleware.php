<?php
require_once __DIR__ . '/Utils.class.php';

class AuthMiddleware
{
	//stub TODO
	function valid_API_key($API_key)
	{
		return true;
	}

	public function __invoke($request, $response, $next)
	{
		if($_SERVER['PATH_INFO'] == '/')
		{
			return $next($request, $response);
		}
		else if(empty($_REQUEST['API']))
		{
			$JSON_Response = Utils::getStatus(401);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}
		else if(!$this->valid_API_key($args['API']))
		{
			$JSON_Response = Utils::getStatus(403);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}

		return $next($request, $response);
	}
}

?>