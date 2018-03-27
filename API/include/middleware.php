<?php

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
			$JSON_Response = array("status" => 401, "msg" => "This route requires and API key and no API key was provided.");
			return $response->withJson($JSON_Response);
		}
		else if(!$this->valid_API_key($args['API']))
		{
			$JSON_Response = array("status" => 403, "msg" => "Invalid API key was provided.");
			return $response->withJson($JSON_Response);
		}

		return $next($request, $response);
	}
}

?>