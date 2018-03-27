<?php
require __DIR__ . "/db.config.php";

class TGDB
{
	private $database;
	private $PlatformsTblCols;
	private $GamesTblCols;

	private function __construct()
	{
		$this->database = sql::getInstance();
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

	function GetGameListByPlatform($IDs = 0, $offset = 0, $limit = 20, $options = array())
	{
		$qry = "Select id, GameTitle, Developer, ReleaseDate ";

		if(!empty($options))
		{
			foreach($options as $key => $enabled)
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
		$qry .= "LIMIT :limit OFFSET :offset;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			if(isset($options['boxart']) && $options['boxart'])
			{
				$GameIDs = array();
				foreach($res as $game)
				{
					$GameIDs[] = $game->id;
				}
				$boxart = $this->GetGameBoxartByID($GameIDs);
				foreach($res as $game)
				{
					if(isset($boxart[$game->id]))
					{
						$game->boxart = $boxart[$game->id];
					}
					else
					{
						$game->boxart = NULL;
					}
				}
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$platforms = $this->GetPlatforms($IDs);
				foreach($res as $game)
				{
					$game->PlatformDetails = $platforms[$game->Platform];
				}
			}
			return $res;
		}
	}

	function GetGameByID($IDs, $offset = 0, $limit = 20, $options = array())
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

		$qry = "Select id, GameTitle, Developer, ReleaseDate ";

		if(!empty($options))
		{
			foreach($options as $key => $enabled)
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

			if(isset($options['boxart']) && isset($options['boxart']))
			{
				$boxart = $this->GetGameBoxartByID($IDs, $offset, $limit);
				foreach($res as $game)
				{
					if(isset($boxart[$game->id]))
					{
						$game->boxart = $boxart[$game->id];
					}
					else
					{
						$game->boxart = NULL;
					}
				}
			}
			if(isset($options['Platform']) && $options['Platform'])
			{
				$PlatformsIDs = array();
				foreach($res as $game)
				{
					$PlatformsIDs[] = $game->Platform;
				}
				$platforms = $this->GetPlatforms($PlatformsIDs);
				if(!empty($platforms))
				{
					foreach($res as $game)
					{
						$game->PlatformDetails = $platforms[$game->Platform];
					}
				}
			}

			return $res;
		}

		return array();
	}

	function SearchGamesByName($searchTerm, $offset = 0, $limit = 20, $options = array())
	{
		$dbh = $this->database->dbh;

		$qry = "Select id, GameTitle, Developer, ReleaseDate ";

		if(!empty($options))
		{
			foreach($options as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", $key ";
				}
			}
		}

		$qry .= " FROM games WHERE GameTitle LIKE :name OR GameTitle=:name2 OR soundex(GameTitle) LIKE soundex(:name3)
		GROUP BY id ORDER BY CASE WHEN GameTitle like :name4 THEN 0
		WHEN GameTitle like :name5 THEN 1
		WHEN GameTitle like :name6 THEN 2
		ELSE 3
		END, GameTitle LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");
		$sth->bindValue(':name5', "%$searchTerm");
		$sth->bindValue(':name6', $searchTerm);


		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			$IDs = array();
			foreach($res as $game)
			{
				$IDs[] = $game->id;
			}
			if(isset($options['boxart']) && $options['boxart'] && !empty($IDs))
			{
				$boxart = $this->GetGameBoxartByID($IDs);
				foreach($res as $game)
				{
					if(!empty($boxart[$game->id]))
					{
						$game->boxart = $boxart[$game->id];
					}
					else
					{
						$game->boxart = NULL;
					}
				}
			}
			return $res;
		}
	}

	//TODO:filter return type
	function GetGameBoxartByID($IDs = 0, $offset = 0, $limit = 20, $filter = 'boxart')
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

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select keyvalue as games_id, keytype as type, filename, resolution FROM banners WHERE keyvalue IN ($GameIDs) LIMIT :limit OFFSET :offset;");
	
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetPlatformsList($options = array())
	{
		$qry = "Select id, name, alias FROM platforms;";

		$dbh = $this->database->dbh;
		if(!empty($options))
		{
			$qry = "Select id, name, alias";
			foreach($options as $key => $enabled)
			{
				if($enabled && $this->is_valid_platform_col($key))
				{
					$qry .= ", $key ";
				}
			}
			$qry .= " FROM platforms;";
		}
		
		$sth = $dbh->prepare($qry);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetPlatforms($IDs, $options = array())
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

		$qry = "Select id, name, alias FROM platforms";

		$dbh = $this->database->dbh;
		if(!empty($options))
		{
			$qry = "Select id, name, alias";
			foreach($options as $key => $enabled)
			{
				if($enabled && $this->is_valid_platform_col($key))
				{
					$qry .= ", $key ";
				}
			}
			$qry .= " FROM platforms ";
		}
		$qry .= " Where id IN ($PlatformIDs);";

		$sth = $dbh->prepare($qry);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
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
