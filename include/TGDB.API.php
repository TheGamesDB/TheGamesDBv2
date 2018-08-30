<?php
require_once __DIR__ . "/db.config.php";

class TGDB
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

	function GetGameListByPlatform($IDs = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games ";


		$PlatformIDs = array();
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$PlatformIDs = $IDs;
		}
		
		if(!empty($PlatformIDs))
		{
			$qry .= "WHERE Platform IN ($PlatformIDs) ";
		}
		if(!empty($OrderBy) && $this->is_valid_games_col($OrderBy))
		{
			if($ASCDESC != 'ASC' && $ASCDESC != 'DESC')
			{
				$ASCDESC == 'ASC';
			}
			$qry .= " ORDER BY $OrderBy $ASCDESC ";
		}
		$qry .= " LIMIT :limit OFFSET :offset;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetGameByID($IDs, $offset = 0, $limit = 20, $fields = array())
	{
		$GameIDs = array();
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$GameIDsArr[] = $val;
			}
			$GameIDs = implode(",", $GameIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$GameIDs = $IDs;
		}
		
		if(empty($GameIDs))
		{
			return array();
		}

		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games WHERE id IN ($GameIDs) LIMIT :limit OFFSET :offset";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}

		return array();
	}

	function SearchGamesByName($searchTerm, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games WHERE GameTitle LIKE :name OR GameTitle=:name2 OR soundex(GameTitle) LIKE soundex(:name3) OR soundex(GameTitle) LIKE soundex(:name4)
		GROUP BY id ORDER BY CASE
		WHEN GameTitle like :name5 THEN 3
		WHEN GameTitle like :name6 THEN 0
		WHEN GameTitle like :name7 THEN 1
		WHEN GameTitle like :name8 THEN 2
		ELSE 4
		END, GameTitle LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name5', "%$searchTerm");
		$sth->bindValue(':name6', $searchTerm);
		$sth->bindValue(':name7', "$searchTerm%");
		$sth->bindValue(':name8', "% %$searchTerm% %");


		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function SearchGamesByNameByPlatformID($searchTerm, $IDs, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$PlatformIDs = array();
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$PlatformIDs = $IDs;
		}

		$qry .= " FROM games WHERE ";

		if(!empty($PlatformIDs))
		{
			$qry .= " Platform IN ($PlatformIDs) AND ";
		}

		$qry .= " (GameTitle LIKE :name OR GameTitle=:name2 OR soundex(GameTitle) LIKE soundex(:name3) OR soundex(GameTitle) LIKE soundex(:name4))
		GROUP BY id ORDER BY CASE
		WHEN GameTitle like :name5 THEN 3
		WHEN GameTitle like :name6 THEN 0
		WHEN GameTitle like :name7 THEN 1
		WHEN GameTitle like :name8 THEN 2
		ELSE 4
		END, GameTitle LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name5', "%$searchTerm");
		$sth->bindValue(':name6', $searchTerm);
		$sth->bindValue(':name7', "$searchTerm%");
		$sth->bindValue(':name8', "% %$searchTerm% %");


		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetGamesByDate($date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		return $this->GetGamesByDateByPlatform(0, $date, $offset, $limit, $fields, $OrderBy, $ASCDESC);
	}

	function GetGamesByDateByPlatform($IDs, $date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		if(isset($fields['BEFORE']))
		{
			$BeforeAfterDate = "<=";
		}
		else
		{
			$BeforeAfterDate = ">";
		}

		$qry .= " FROM games WHERE ReleaseDateRevised $BeforeAfterDate STR_TO_DATE(:date, '%d/%m/%Y') ";

		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
			$qry .= " AND Platform IN " . implode(",", $PlatformIDsArr) . " ";
		}
		else if(is_numeric($IDs) && $IDs > 0)
		{
			$qry .= " AND Platform = $IDs ";
		}


		if(!empty($OrderBy) && $this->is_valid_games_col($OrderBy))
		{
			if($ASCDESC != 'ASC' && $ASCDESC != 'DESC')
			{
				$ASCDESC == 'ASC';
			}
			$qry .= " ORDER BY $OrderBy $ASCDESC";
		}
		$qry .= " LIMIT :limit OFFSET :offset";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':date', $date, PDO::PARAM_STR);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}

		return array();
	}

	function GetAllGames($offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games ";
		if(!empty($OrderBy) && $this->is_valid_games_col($OrderBy))
		{
			if($ASCDESC != 'ASC' && $ASCDESC != 'DESC')
			{
				$ASCDESC == 'ASC';
			}
			$qry .= " ORDER BY $OrderBy $ASCDESC";
		}
		$qry .= " LIMIT :limit OFFSET :offset";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}

		return array();
	}

	function GetGamesByLatestUpdatedDate($minutes, $offset = 0, $limit = 20, $fields = array())
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate, ReleaseDateRevised, Platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games WHERE lastupdatedRevised > DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -:minutes MINUTE) ORDER BY lastupdatedRevised DESC LIMIT :limit OFFSET :offset";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':minutes', $minutes, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}

		return array();
	}

	private function CreateBoxartFilterQuery($filter, &$is_filter)
	{
		$qry = "";
		switch($filter)
		{
			case 'fanart':
			case 'series':
			case 'boxart':
			case 'screenshot':
			case 'platform-banner':
			case 'platform-fanart':
			case 'platform-boxart':
			case 'clearlogo':
				if(!$is_filter)
				{
					$qry .=" AND (";
					$is_filter = true;

				}
				else
				{
					$qry .=" OR ";
				}
				$qry .=" keytype = '$filter' ";
		}
		return $qry;
	}

	function GetGameBoxartByID($IDs = 0, $offset = 0, $limit = 20, $filters = 'boxart')
	{
		$GameIDs;
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$GameIDsArr[] = $val;
			}
			$GameIDs = implode(",", $GameIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$GameIDs = $IDs;
		}

		if(empty($GameIDs))
		{
			return array();
		}

		$qry = "Select keyvalue as games_id, keytype as type, side, filename, resolution FROM banners WHERE keyvalue IN ($GameIDs) ";
		$is_filter = false;
		if(is_array($filters))
		{
			foreach($filters as $filter)
			{
				$qry .= $this->CreateBoxartFilterQuery($filter, $is_filter);
			}
		}
		else
		{
			$qry .= $this->CreateBoxartFilterQuery($filters, $is_filter);
		}

		if($is_filter)
		{
			$qry .=" )";
		}
		$qry .= " LIMIT :limit OFFSET :offset;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
	
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetLatestGameBoxart($offset = 0, $limit = 20, $filters = 'boxart', $side = '')
	{
		$qry = "Select keyvalue as game_id, keytype as type, side, filename, resolution FROM banners WHERE 1 ";
		$is_filter = false;
		if(is_array($filters))
		{
			foreach($filters as $filter)
			{
				$qry .= $this->CreateBoxartFilterQuery($filter, $is_filter);
			}
		}
		else
		{
			$qry .= $this->CreateBoxartFilterQuery($filters, $is_filter);
		}

		if($is_filter)
		{
			$qry .= " )";
		}
		if(!empty($side) && ($side == 'front' || $side == 'back'))
		{
			$qry .= " AND side = '$side' ";
		}
		$qry .= " ORDER BY id DESC  LIMIT :limit OFFSET :offset;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetPlatformsList($fields = array())
	{
		$qry = "Select id as n, id, name, alias ";

		$dbh = $this->database->dbh;
		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_platform_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM platforms ORDER BY name;";

		$sth = $dbh->prepare($qry);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetPlatforms($IDs, $fields = array())
	{
		$PlatformIDs;
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$PlatformIDs = $IDs;
		}

		if(empty($PlatformIDs))
		{
			return array();
		}

		$qry = "Select id as n, id, name, alias ";

		$dbh = $this->database->dbh;
		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_platform_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}
		$qry .= " FROM platforms Where id IN ($PlatformIDs) ORDER BY name;";

		$sth = $dbh->prepare($qry);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function SearchPlatformByName($searchTerm, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry = "Select id, name, alias";

		$dbh = $this->database->dbh;
		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_platform_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM platforms WHERE name LIKE :name OR name=:name2 OR soundex(name) LIKE soundex(:name3) OR soundex(name) LIKE soundex(:name4)
		GROUP BY id ORDER BY CASE
		WHEN name like :name5 THEN 3
		WHEN name like :name6 THEN 0
		WHEN name like :name7 THEN 1
		WHEN name like :name8 THEN 2
		ELSE 4
		END, name";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name5', "%$searchTerm");
		$sth->bindValue(':name6', $searchTerm);
		$sth->bindValue(':name7', "$searchTerm%");
		$sth->bindValue(':name8', "% %$searchTerm% %");

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetGamesStats()
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("SELECT type, count from statistics WHERE type != 'boxart' AND type NOT LIKE 'plat%' ORDER BY type");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
			{
				$ret =  $dbh->query("select count(id) from games where Overview is not null", PDO::FETCH_COLUMN, 0);
				$res['overview'] = $ret->fetch();
			}
			return $res;
		}
	}

	function GetPubsList()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("SELECT id as n, id, name FROM pubs_list order by name");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP| PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetPubsListByIDs($IDs)
	{
		$dbh = $this->database->dbh;
		$devs_IDs;
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$valid_ids[] = $val;
			}
			if(!empty($valid_ids))
			{
				$devs_IDs = implode(",", $valid_ids);
			}
		}
		else if(is_numeric($IDs))
		{
			$devs_IDs = $IDs;
		}
		if(empty($devs_IDs))
		{
			return array();
		}
		$sth = $dbh->prepare("SELECT id as n, id, name FROM pubs_list where id IN($devs_IDs) order by name");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP| PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	//TO SLOW, so instead create a DB that I'd update periodically cron job
	function UpdateStats()
	{
		$dbh = $this->database->dbh;

		$Total = array();
		$sth = $dbh->prepare("SELECT DISTINCT keytype from banners");
		if($sth->execute())
		{
			$types = $sth->fetchAll(PDO::FETCH_COLUMN);
			foreach($types as $type)
			{
				$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where keytype = :type and keyvalue IN (select id from games) GROUP BY keyvalue");
				$sth->bindValue(':type', $type, PDO::PARAM_STR);
				if($sth->execute())
				{
					$Total[$type] = $sth->rowCount();
				}
			}
			$Total['boxart'] = array();
			$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where keytype = 'boxart' and side = 'front' and keyvalue IN (select id from games) GROUP BY keyvalue");
			if($sth->execute())
			{
				$Total['boxart']['front'] = $sth->rowCount();
			}
			$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where keytype = 'boxart' and side = 'back' and keyvalue IN (select id from games) GROUP BY keyvalue");
			if($sth->execute())
			{
				$Total['boxart']['back'] = $sth->rowCount();
			}

			foreach($Total as $index => $val)
			{
				if(!is_array($val))
				{
					$sth = $dbh->prepare("UPDATE statistics SET count = :count WHERE type = :type");
					$sth->bindValue(':type', $index, PDO::PARAM_STR);
					$sth->bindValue(':count', $val, PDO::PARAM_STR);
					$sth->execute();
				}
				else
				{
					foreach($val as $key => $val2)
					{
						$sth = $dbh->prepare("UPDATE statistics SET count = :count WHERE type = :type");
						$sth->bindValue(':type', "$index-$key", PDO::PARAM_STR);
						$sth->bindValue(':count', $val2, PDO::PARAM_STR);
						$sth->execute();
					}
				}
			}
		}
	}

	function is_valid_platform_col($name)
	{
		if(empty($this->PlatformsTblCols))
		{
			$dbh = $this->database->dbh;
			$sth = $dbh->prepare("DESCRIBE platforms");
			if($sth->execute())
			{
				$res = $sth->fetchAll(PDO::FETCH_COLUMN);
				foreach($res as $index => $val)
				{
					$this->PlatformsTblCols[$val] = true;
				}
			}
		}
		return isset($this->PlatformsTblCols[$name]);
	}

	function is_valid_games_col($name)
	{
		if(empty($this->GamesTblCols))
		{
			$dbh = $this->database->dbh;
			$sth = $dbh->prepare("DESCRIBE games");
			if($sth->execute())
			{
				$res = $sth->fetchAll(PDO::FETCH_COLUMN);
				foreach($res as $index => $val)
				{
					$this->GamesTblCols[$val] = true;
				}
			}
		}
		return isset($this->GamesTblCols[$name]);
	}

	/* Everything belowis not planned to be exposed through external API */
	function InsertUserEdits($user_id, $game_id, $type, $diff, $subtype = '')
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT INTO user_edits (users_id, games_id, type, diff) VALUES (:users_id, :games_id, :type, :diff);");
		$sth->bindValue(':users_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_INT);
		$sth->bindValue(':diff', $diff, PDO::PARAM_STR);
		return $sth->execute();
	}

	function UpdateGame($user_id, $game_id, $GameTitle, $Overview, $Youtube, $ReleaseDateRevised, $Players, $coop, $Developer, $Publisher)
	{
		$dbh = $this->database->dbh;
		{
			$sth = $dbh->prepare("Select * FROM games WHERE id = :game_id");
			$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);

			if($sth->execute())
			{
				$Game = $sth->fetch(PDO::FETCH_ASSOC);
			}
			if(!isset($Game) || empty($Game))
			{
				return false;
			}
		}

		{
			$dbh->beginTransaction();

			$sth = $dbh->prepare("UPDATE games SET GameTitle=:GameTitle, Overview=:Overview, ReleaseDateRevised=:ReleaseDateRevised, ReleaseDate=:ReleaseDate, Players=:Players, coop=:coop,
			Developer=:Developer, Publisher=:Publisher, Youtube=:YouTube WHERE id=:game_id");
			$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);
			$sth->bindValue(':GameTitle', htmlspecialchars($GameTitle), PDO::PARAM_STR);
			$sth->bindValue(':Overview', htmlspecialchars($Overview), PDO::PARAM_STR);
			$sth->bindValue(':ReleaseDateRevised', $ReleaseDateRevised, PDO::PARAM_STR);
			$date = explode('-', $ReleaseDateRevised);
			$sth->bindValue(':ReleaseDate', "$date[1]/$date[2]/$date[0]", PDO::PARAM_STR);
			$sth->bindValue(':Players', $Players, PDO::PARAM_INT);
			$sth->bindValue(':YouTube', htmlspecialchars($Youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);

			// NOTE: these will be moved to own table, as a single game can have multiple devs/publishers
			// it will also mean, we will be able to standardise devs/publishers names
			// this will allow their selection from a menu as oppose to being provided by the user
			$sth->bindValue(':Developer', htmlspecialchars($Developer), PDO::PARAM_STR);
			$sth->bindValue(':Publisher', htmlspecialchars($Publisher), PDO::PARAM_STR);

			$sth->execute();
			{
				foreach($Game as $key => $value)
				{
					if(isset($$key) && htmlspecialchars($$key) != $value)
					{
						if($key == 'Overview')
						{
							$diff = xdiff_string_diff($Game['Overview'], htmlspecialchars($Overview), 1);
							if(empty($diff))
							{
								continue;
							}
						}
						else
						{
							$diff = htmlspecialchars($$key);
						}
						$this->InsertUserEdits($user_id, $game_id, $key, $diff);
					}
				}
			}
			return $dbh->commit();
		}
	}

	function DeleteGameImages($user_id, $game_id, $id, $type)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("DELETE FROM banners WHERE id=:id;");
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$res = $sth->execute();
		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $game_id, $type, "[REMOVED]");
		}
		return ($dbh->inTransaction() || $res);
	}

	function DeleteAllGameImages($user_id, $game_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("DELETE FROM banners WHERE keyvalue=:game_id;");
		$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);
		$res = $sth->execute();
		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $game_id, "all_images", "[REMOVED]");
		}
		return ($dbh->inTransaction() || $res);
	}

	function DeleteAndInsertGameImages($user_id, $id, $game_id, $type, $filename, $side = NULL)
	{
		$dbh = $this->database->dbh;
		$dbh->beginTransaction();
		$this->DeleteGameImages($user_id, $game_id, $id, $type);
		$this->InsertGameImages($user_id, $game_id, $type, $filename, $side);
		return $dbh->commit();

	}

	function InsertGameImages($user_id, $game_id, $type, $filename, $side = NULL)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("INSERT INTO banners (keyvalue, keytype, side, filename, userid) VALUES (:keyvalue, :keytype, :side, :filename, :user_id); ");
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':keyvalue', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':keytype', $type, PDO::PARAM_STR);
		$sth->bindValue(':side', $side, PDO::PARAM_STR);
		$sth->bindValue(':filename', $filename, PDO::PARAM_STR);
		$res = $sth->execute();

		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $game_id, $type, $filename);
		}
		return ($dbh->inTransaction() || $res);
	}
	function DeleteGame($user_id, $game_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("DELETE FROM games WHERE id=:game_id;");
		$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);
		$res = $sth->execute();
		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $game_id, "game", "[REMOVED]");
		}
		return ($dbh->inTransaction() || $res);
	}

	function InsertGame($user_id, $GameTitle, $Overview, $Youtube, $ReleaseDateRevised, $Players, $coop, $Developer, $Publisher, $Platform)
	{
		$game_id = 0;
		$dbh = $this->database->dbh;
		{
			$sth = $dbh->prepare("INSERT INTO games(GameTitle, Overview, ReleaseDateRevised, ReleaseDate, Players, coop, Developer, Publisher, Youtube, Alternates, Platform)
			values (:GameTitle, :Overview, :ReleaseDateRevised, :ReleaseDate, :Players, :coop, :Developer, :Publisher, :YouTube, :Alternates, :Platform)");
			$sth->bindValue(':GameTitle', htmlspecialchars($GameTitle), PDO::PARAM_STR);
			$sth->bindValue(':Overview', htmlspecialchars($Overview), PDO::PARAM_STR);
			$sth->bindValue(':ReleaseDateRevised', $ReleaseDateRevised, PDO::PARAM_STR);
			$date = explode('-', $ReleaseDateRevised);
			$sth->bindValue(':ReleaseDate', "$date[1]/$date[2]/$date[0]", PDO::PARAM_STR);
			$sth->bindValue(':Players', $Players, PDO::PARAM_INT);
			$sth->bindValue(':YouTube', htmlspecialchars($Youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);
			$sth->bindValue(':Alternates', "", PDO::PARAM_STR);
			$sth->bindValue(':Platform', $Platform, PDO::PARAM_INT);

			// NOTE: these will be moved to own table, as a single game can have multiple devs/publishers
			// it will also mean, we will be able to standardise devs/publishers names
			// this will allow their selection from a menu as oppose to being provided by the user
			$sth->bindValue(':Developer', htmlspecialchars($Developer), PDO::PARAM_STR);
			$sth->bindValue(':Publisher', htmlspecialchars($Publisher), PDO::PARAM_STR);

			if($sth->execute())
			{
				$game_id = $dbh->lastInsertId();
				$dbh->beginTransaction();
				$this->InsertUserEdits($user_id, $game_id, 'game', '[NEW]');

				$GameArrayFields = ['GameTitle', 'Overview', 'ReleaseDateRevised', 'Players', 'coop', 'Developer', 'Publisher', 'Youtube'];
				foreach($GameArrayFields as $key)
				{
					$diff = htmlspecialchars($$key);
					$this->InsertUserEdits($user_id, $game_id, $key, $diff);
				}
				$dbh->commit();
			}
		}
		return $game_id;
	}
}

?>
