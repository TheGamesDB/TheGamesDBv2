<?php
require_once __DIR__ . '/Utils.class.php';
require_once __DIR__ . '/APIAccessDB.class.php';

class AuthMiddleware
{
	public function __invoke($request, $response, $next)
	{
		if($_SERVER['PATH_INFO'] == '/')
		{
			return $next($request, $response);
		}
		else if(empty($_REQUEST['apikey']))
		{
			$JSON_Response = Utils::getStatus(401);
			return $response->withJson($JSON_Response, $JSON_Response['code']);
		}
		else
		{
			$auth = APIAccessDB::getInstance();
			$User = $auth->GetUserByAPIKey($_REQUEST['apikey']);
			if(!empty($User))
			{
				$monthly_allowance = (!empty($User->monthly_allowance)) ? $User->monthly_allowance : 0;
				$monthly_count = (!empty($User->count)) ? $User->count : 0;
				if($update_refresh_date = strtotime($User->last_refresh_date) < strtotime('-30 days'))
				{
					$monthly_count = 0;
				}
				$remaining_monthly_allowance = $monthly_allowance - $monthly_count;
				// TODO based on weather this will be a subscription based or allowance purchase
				// 1) if subscription based we need to count 30 days instead of using start of the month
				// 2) if allowance purchase, just countdown from their allowance
				if($remaining_monthly_allowance > 0)
				{
					$response = $next($request, $response);
					$auth->decreaseAllowanceCounter($User, $update_refresh_date);
					$JSON_Response = json_decode($response->getBody(), true);
					$JSON_Response['remaining_monthly_allowance'] = $remaining_monthly_allowance + $User->extra_allowance - 1;
					return $response->withJson($JSON_Response, $JSON_Response['code']);
				}
				else if($User->extra_allowance > 0)
				{
					$response = $next($request, $response);
					$auth->decreaseExtraAllowanceCounter($User, $update_refresh_date);
					$JSON_Response = json_decode($response->getBody(), true);
					$JSON_Response['remaining_monthly_allowance'] = $User->extra_allowance - 1;
					return $response->withJson($JSON_Response, $JSON_Response['code']);
				}
				else
				{
					$JSON_Response = Utils::getStatus(403);
					$JSON_Response['remaining_monthly_allowance'] = 0;
					return $response->withJson($JSON_Response, $JSON_Response['code']);
				}
			}
			else
			{
				$JSON_Response = Utils::getStatus(401);
				$JSON_Response['remaining_monthly_allowance'] = 0;
				return $response->withJson($JSON_Response, $JSON_Response['code']);
			}
		}
	}
}

?>