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
		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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

		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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

		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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

	function GetGamesByDate($date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		return $this->GetGamesByDateByPlatform(0, $date, $offset, $limit, $fields, $OrderBy, $ASCDESC);
	}

	function GetGamesByDateByPlatform($IDs, $date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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
		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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
		$qry = "Select id, GameTitle, Developer, ReleaseDate, Platform ";

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
		$qry = "Select id, name, alias ";

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
}


?>
