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
		$sth = $dbh->prepare("Select APIU.*, AA.monthly_allowance, sum(AMC.count) as count FROM apiusers APIU
		LEFT JOIN api_allowance_level AA ON AA.id = APIU.api_allowance_level_id
		LEFT JOIN api_month_counter AMC ON AMC.apiusers_id = APIU.id AND AMC.date >= APIU.last_refresh_date AND AMC.is_extra = 0
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
			$sth = $dbh->prepare("INSERT INTO api_month_counter (apiusers_id, count, is_extra, date)
			VALUES (:id, 1, :is_extra, :date) ON DUPLICATE KEY UPDATE count = count + 1;");
			$sth->bindValue(':date', date('Y-m-d'));
			$sth->bindValue(':id', $User->id, PDO::PARAM_INT);
			$sth->bindValue(':is_extra', $is_extra, PDO::PARAM_INT);
			$sth->execute();
			$dbh->commit();
		}
		catch (Exception $e)
		{
			$dbh->commit();
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