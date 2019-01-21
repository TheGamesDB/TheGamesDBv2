<?php
require_once __DIR__ . '/Utils.class.php';
require_once __DIR__ . '/APIAccessDB.class.php';

class AuthMiddleware
{
	public function __invoke($request, $response, $next)
	{
		if(!isset($_SERVER['REDIRECT_URL']) && !isset($_SERVER['PATH_INFO']))
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
			$User = $auth->GetUserAllowanceByAPIKey($_REQUEST['apikey']);
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

					$response = $next($request, $response);
					$auth->countAPIRequest($User, $update_refresh_date, $use_extra);
					$JSON_Response = json_decode($response->getBody(), true);
					$JSON_Response['remaining_monthly_allowance'] = $remaining_monthly_allowance + (!$use_extra ? -1 : 0);
					$JSON_Response['extra_allowance'] =  $User->extra_allowance + ($use_extra ? -1 : 0);
					$JSON_Response['allowance_refresh_timer'] = ($User->is_private_key == 1) ? NULL : $refresh;
					return $response->withJson($JSON_Response, isset($JSON_Response['code'])  ? $JSON_Response['code'] : 200);
				}
				else
				{
					$JSON_Response = Utils::getStatus(403);
					$JSON_Response['remaining_monthly_allowance'] = 0;
					$JSON_Response['allowance_refresh_timer'] = ($User->is_private_key == 1) ? NULL : $refresh;
					return $response->withJson($JSON_Response, $JSON_Response['code']);
				}
			}
			else
			{
				$JSON_Response = Utils::getStatus(401);
				$JSON_Response['remaining_monthly_allowance'] = 0;
				$JSON_Response['allowance_refresh_timer'] = 0;
				return $response->withJson($JSON_Response, $JSON_Response['code']);
			}
		}
	}
}

?>
