<?php
require_once __DIR__ . "/../../include/db.config.php";

class APIAccessDB
{
	private $database;
	private $PlatformsTblCols;
	private $GamesTblCols;

	private function __construct()
	{
		$this->database = database::getInstance();
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

	function GetUserByAPIKey($key)
	{

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select APIU.*, AA.monthly_allowance FROM apiusers APIU
		LEFT JOIN api_allowance_level AA ON AA.id = APIU.api_allowance_level_id
		WHERE apikey=:apikey;");
		$sth->bindValue(':apikey', $key, PDO::PARAM_STR);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetUserByAPIKey_PLACEHOLDER($key)
	{

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select APIU.*, AMC.id as c_id, AMC.count, AA.monthly_allowance FROM apiusers APIU
		LEFT JOIN api_month_counter AMC ON APIU.id = AMC.apiusers_id AND AMC.month=:month AND AMC.year=:year
		LEFT JOIN api_allowance_level AA ON AA.id = APIU.api_allowance_level_id
		WHERE apikey=:apikey ;");

		$sth->bindValue(':apikey', $key, PDO::PARAM_STR);
		$sth->bindValue(':month', date("m"), PDO::PARAM_INT);
		$sth->bindValue(':year', date("Y"), PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function decreaseAllowanceCounter($User, $update_refresh_date)
	{
		try
		{
			$dbh = $this->database->dbh;			
			if($update_refresh_date)
			{
				$sth = $dbh->prepare("UPDATE apiusers SET count = 1, last_refresh_date=:date WHERE id=:id;");
				$sth->bindValue(':date', date('Y-m-d'));
			}
			else
			{
				$sth = $dbh->prepare("UPDATE apiusers SET count = count+1 WHERE id=:id;");			
			}
			$sth->bindValue(':id', $User->id, PDO::PARAM_INT);

			$i = 1;
			while(!$sth->execute())
			{
				if($i > 3)
				{
					break;
				}
				$i++;
			}
		}
		catch (Exception $e)
		{
			//Free lunch :P
		}
	}

	function decreaseExtraAllowanceCounter($User)
	{
		try
		{
			$dbh = $this->database->dbh;

			$sth = $dbh->prepare("UPDATE apiusers SET extra_allowance = extra_allowance-1 WHERE id=:id;");
			$sth->bindValue(':id', $User->id, PDO::PARAM_INT);

			$i = 1;
			while(!$sth->execute())
			{
				if($i > 3)
				{
					break;
				}
				$i++;
			}
		}
		catch (Exception $e)
		{
			//Free lunch :P
		}
	}

	function increaseAllowanceCounter($User)
	{
		try
		{		
			$dbh = $this->database->dbh;
			if(!empty($User->c_id))
			{
				$sth = $dbh->prepare("UPDATE api_month_counter SET count = count+1 WHERE id=:id;");
				$sth->bindValue(':id', $User->c_id, PDO::PARAM_INT);
			}
			else
			{
				$sth = $dbh->prepare("INSERT INTO api_month_counter (apiusers_id, count, month, year)
				VALUES (:apiusers_id, 1, :month, :year);");
				$sth->bindValue(':apiusers_id', $User->id, PDO::PARAM_INT);
				$sth->bindValue(':month', date("m"), PDO::PARAM_INT);
				$sth->bindValue(':year', date("Y"), PDO::PARAM_INT);
			}
			$i = 1;
			while(!$sth->execute())
			{
				if($i > 3)
				{
					break;
				}
				$i++;
			}
		}
		catch (Exception $e)
		{
			//Free lunch :P
		}
	}
}