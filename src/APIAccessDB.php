<?php

namespace TheGamesDB;

use PDO;
use Exception;

class APIAccessDB
{
	private $database;
	private $PlatformsTblCols;
	private $GamesTblCols;

	private function __construct()
	{
		$this->database = Database::getInstance();
	}

	public static function getInstance()
	{
		static $instance = null;
		if (!isset($instance))
		{
			$object = __CLASS__;
			$instance = new $object;
		}
		return $instance;
	}

	function GetUserAllowanceByAPIKey($key)
	{

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select APIU.*, AA.monthly_allowance, sum(AMC.count) as count FROM apiusers APIU
		LEFT JOIN api_allowance_level AA ON AA.id = APIU.api_allowance_level_id
		LEFT JOIN api_month_counter AMC ON AMC.IP = INET6_ATON(:IP) AND AMC.apiusers_id = APIU.id AND AMC.date >= APIU.last_refresh_date AND AMC.is_extra = 0
		WHERE apikey=:apikey GROUP BY APIU.id;");
		$sth->bindValue(':apikey', $key, PDO::PARAM_STR);
		$sth->bindValue(':IP', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function countAPIRequest($User, $update_refresh_date, $is_extra)
	{
		$dbh = $this->database->dbh;
		try
		{
			$dbh->beginTransaction();
			if($update_refresh_date)
			{
				$sth = $dbh->prepare("UPDATE apiusers SET last_refresh_date=:date WHERE id=:id;");
				$sth->bindValue(':date', date('Y-m-d'));
				$sth->bindValue(':id', $User->id, PDO::PARAM_INT);
				$sth->execute();
			}
			if($is_extra)
			{
				$sth = $dbh->prepare("UPDATE apiusers SET extra_allowance = extra_allowance - 1 WHERE id=:id;");
				$sth->bindValue(':id', $User->id, PDO::PARAM_INT);
				$sth->execute();
			}
			$sth = $dbh->prepare("INSERT INTO api_month_counter (apiusers_id, count, is_extra, date, IP)
			VALUES (:id, 1, :is_extra, :date, INET6_ATON(:IP)) ON DUPLICATE KEY UPDATE count = count + 1;");
			$sth->bindValue(':date', date('Y-m-d'));
			$sth->bindValue(':id', $User->id, PDO::PARAM_INT);
			$sth->bindValue(':is_extra', $is_extra, PDO::PARAM_INT);
			$sth->bindValue(':IP', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
			$sth->execute();
			$dbh->commit();
		}
		catch (Exception $e)
		{
			$dbh->commit();
			//Free lunch :P
		}
	}

	function RequestPublicAPIKey($user_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select apikey, is_banned FROM apiusers where userid = :user_id AND is_private_key = 0 LIMIT 1;");
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ);
			if(!empty($res))
			{
				if($res->is_banned == 0)
				{
					return $res->apikey;
				}
				else
				{
					return "Access Denied";
				}
			}
			else
			{
				$bytes = openssl_random_pseudo_bytes(64/2);
				$key = bin2hex($bytes);
				$sth = $dbh->prepare("INSERT INTO apiusers (userid, apikey, api_allowance_level_id, extra_allowance, is_private_key)
				VALUES(:user_id, :apikey, 1, 0, 0);");
				$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
				$sth->bindValue(':apikey', $key, PDO::PARAM_INT);

				if($sth->execute())
				{
					return $key;
				}
				else
				{
					return "Failed to generate API Key.";
				}
			}
		}
	}

	function RequestPrivateAPIKey($user_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select apikey, extra_allowance, is_banned FROM apiusers where userid = :user_id AND is_private_key != 0 LIMIT 1;");
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ);
			if(!empty($res))
			{
				if($res->is_banned == 0)
				{
					return $res;
				}
				else
				{
					return "Access Denied";
				}
			}
			else
			{
				$bytes = openssl_random_pseudo_bytes(64/2);
				$key = bin2hex($bytes);
				$sth = $dbh->prepare("INSERT INTO apiusers (userid, apikey, api_allowance_level_id, extra_allowance, is_private_key)
				VALUES(:user_id, :apikey, 0, 6000, 1);");
				$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
				$sth->bindValue(':apikey', $key, PDO::PARAM_INT);

				if($sth->execute())
				{
					return $this->RequestPrivateAPIKey($user_id);
				}
				else
				{
					return "Failed to generate API Key.";
				}
			}
		}
	}
}
