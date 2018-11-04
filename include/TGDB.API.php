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

	private function PopulateOtherData(&$res, $fields)
	{
		$GameIDs = array();
		foreach($res as $game)
		{
			$GameIDs[] = $game->id;
		}
		$devs = $this->GetGamesDevs($GameIDs, false);
		if(isset($fields['genres']))
		{
			$genres = $this->GetGamesGenres($GameIDs, false);
		}
		if(isset($fields['publishers']))
		{
			$pubs = $this->GetGamesPubs($GameIDs, false);
		}
		
		if(isset($fields['alternates']))
		{
			$alts = $this->GetGamesAlts($GameIDs, false);
		}
		foreach($res as $game)
		{
			$game->developers = (!empty($devs[$game->id])) ? $devs[$game->id] : NULL;
			if(isset($fields['genres']))
			{
				$game->genres = (!empty($genres[$game->id])) ? $genres[$game->id] : NULL;
			}
			if(isset($fields['publishers']))
			{
				$game->publishers = (!empty($pubs[$game->id])) ? $pubs[$game->id] : NULL;
			}
			if(isset($fields['alternates']))
			{
				$game->alternates = !empty($alts[$game->id]) ? $alts[$game->id] : NULL;
			}
		}
	}

	function GetGameListByPlatform($IDs = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform ";

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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
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

		$qry = "Select id, game_title, release_date, platform ";

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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}

		return array();
	}

	function SearchGamesByName($searchTerm, $offset = 0, $limit = 20, $fields = array())
	{
		return $this->SearchGamesByNameByPlatformID($searchTerm, '', $offset, $limit, $fields);
	}

	function SearchGamesByExactName($searchTerm, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry = "Select id, game_title, release_date, platform ";

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

		$qry .= " FROM games WHERE game_title=:game_title LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':game_title', $searchTerm);

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}
	}

	function SearchGamesByNameByPlatformID($searchTerm, $IDs, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

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

		$qry = "Select games.id, games.game_title, games.release_date, games.platform ";

		if(!empty($fields))
		{
			foreach($fields as $key => $enabled)
			{
				if($enabled && $this->is_valid_games_col($key))
				{
					$qry .= ", games.$key ";
				}
			}
		}
		$qry .= " FROM games,
		(
			SELECT games_names_merged.id, @rank := @rank + 1 AS rank FROM
			(
				(
					SELECT games_id as id, name as game_title, SOUNDEX from games_alts
					WHERE name LIKE :name OR name=:name2 OR SOUNDEX LIKE soundex(:name3) OR SOUNDEX LIKE soundex(:name4)
				)
				UNION
				(
					SELECT id, game_title, SOUNDEX from games
					WHERE game_title LIKE :name_2 OR game_title=:name_2_2 OR SOUNDEX LIKE soundex(:name_2_3) OR SOUNDEX LIKE soundex(:name_2_4)
				)
			) games_names_merged, (SELECT @rank := 0) t1
			ORDER BY CASE
			WHEN game_title like :name_3_2 THEN 0
			WHEN game_title like :name_3_3 THEN 1
			WHEN game_title like :name_3_4 THEN 2
			WHEN game_title like :name_3 THEN 3
			ELSE 4
			END, game_title
		) games_ordered where games.id = games_ordered.id ";
		if(!empty($PlatformIDs))
		{
			$qry .= " AND games.platform IN ($PlatformIDs) ";
		}
		$qry .= "GROUP BY games.id ORDER BY MIN(games_ordered.rank) LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name_2', "%$searchTerm%");
		$sth->bindValue(':name_2_2', $searchTerm);
		$sth->bindValue(':name_2_3', "$searchTerm%");
		$sth->bindValue(':name_2_4', "% %$searchTerm% %");

		$sth->bindValue(':name_3', "%$searchTerm");
		$sth->bindValue(':name_3_2', $searchTerm);
		$sth->bindValue(':name_3_3', "$searchTerm%");
		$sth->bindValue(':name_3_4', "% %$searchTerm% %");

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}
	}

	function GetGamesByDate($date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		return $this->GetGamesByDateByPlatform(0, $date, $offset, $limit, $fields, $OrderBy, $ASCDESC);
	}

	function GetGamesByDateByPlatform($IDs, $date, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform ";

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

		$qry .= " FROM games WHERE release_date $BeforeAfterDate STR_TO_DATE(:date, '%d/%m/%Y') ";

		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
			$qry .= " AND platform IN " . implode(",", $PlatformIDsArr) . " ";
		}
		else if(is_numeric($IDs) && $IDs > 0)
		{
			$qry .= " AND platform = $IDs ";
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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}

		return array();
	}

	function GetAllGames($offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform ";

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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}

		return array();
	}

	function GetGamesByLatestUpdatedDate($minutes, $offset = 0, $limit = 20, $fields = array())
	{
		$qry = "Select id, game_title, release_date, platform ";

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

		$qry .= " FROM games WHERE last_updated > DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -:minutes MINUTE) ORDER BY last_updated DESC LIMIT :limit OFFSET :offset";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		$sth->bindValue(':minutes', $minutes, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}

		return array();
	}

	function GetGamesByDevID($IDs = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform ";

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

		$DevIDs = array();
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$DevIDsArr[] = $val;
			}
			$DevIDs = implode(",", $DevIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$DevIDs = $IDs;
		}

		if(empty($DevIDs))
		{
			return array();
		}

		$qry .= "WHERE id IN (SELECT games_id from games_devs where dev_id IN ($DevIDs)) ";
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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}
	}

	function GetGamesByPubID($IDs = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform ";

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

		$PubIDs = array();
		if(is_array($IDs))
		{
			if(!empty($IDs))
			{
				foreach($IDs as $key => $val)
					if(is_numeric($val))
						$PubIDsArr[] = $val;
			}
			$PubIDs = implode(",", $PubIDsArr);
		}
		else if(is_numeric($IDs))
		{
			$PubIDs = $IDs;
		}

		if(empty($PubIDs))
		{
			return array();
		}

		$qry .= "WHERE id IN (SELECT games_id from games_pubs where pub_id IN ($PubIDs)) ";
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
			if(!empty($res))
			{
				$this->PopulateOtherData($res, $fields);
			}
			return $res;
		}
	}

	function GetMissingGames($field, $platform_ids = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		if(!$this->is_valid_games_col($field))
		{
			return array();
		}

		if(is_array($platform_ids))
		{
			if(!empty($platform_ids))
			{
				foreach($platform_ids as $platform_id)
					if(is_numeric($platform_id))
						$valid_platform_ids_arr[] = $platform_id;
			}
			$valid_platform_ids = implode(",", $valid_platform_ids_arr);
		}
		else if(is_numeric($platform_ids))
		{
			$valid_platform_ids = $platform_ids;
		}

		$qry = "Select id, game_title, release_date, platform ";

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


		$qry .= "WHERE ($field = '' OR $field IS NULL) ";
		if(isset($valid_platform_ids))
		{
			$qry .= "AND (platform IN ($valid_platform_ids)) ";
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
			{
				foreach($res as $game)
				{
					$GameIDs[] = $game->id;
				}
				$devs = $this->GetGamesDevs($GameIDs, false);
				foreach($res as $game)
				{
					$game->developers = (!empty($devs[$game->id])) ? $devs[$game->id] : NULL;
				}
				if(isset($fields['publishers']))
				{
					$pubs = $this->GetGamesPubs($GameIDs, false);
					foreach($res as $game)
					{
						$game->publishers = (!empty($pubs[$game->id])) ? $pubs[$game->id] : NULL;
					}
				}
			}
			return $res;
		}
	}

	function GetMissingGamesImages($type, $sub_type = '', $platform_ids = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{

		if(is_array($platform_ids))
		{
			if(!empty($platform_ids))
			{
				foreach($platform_ids as $platform_id)
					if(is_numeric($platform_id))
						$valid_platform_ids_arr[] = $platform_id;
			}
			$valid_platform_ids = implode(",", $valid_platform_ids_arr);
		}
		else if(is_numeric($platform_ids))
		{
			$valid_platform_ids = $platform_ids;
		}
		$qry = "Select id, game_title, release_date, platform ";

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

		$side = '';
		if($sub_type != '')
		{
			$side = 'AND side=:side';
		}
		$qry .= "WHERE (id NOT IN (select games_id from banners where type=:type $side)) ";
		if($platform_id > 0)
		{
			$qry .= "AND (platform IN ($valid_platform_ids)) ";
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

		$sth->bindValue(':type', $type, PDO::PARAM_STR);
		if($sub_type != '')
		{
			$sth->bindValue(':side', $sub_type, PDO::PARAM_STR);
		}
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			{
				foreach($res as $game)
				{
					$GameIDs[] = $game->id;
				}
				$devs = $this->GetGamesDevs($GameIDs, false);
				foreach($res as $game)
				{
					$game->developers = (!empty($devs[$game->id])) ? $devs[$game->id] : NULL;
				}
				if(isset($fields['publishers']))
				{
					$pubs = $this->GetGamesPubs($GameIDs, false);
					foreach($res as $game)
					{
						$game->publishers = (!empty($pubs[$game->id])) ? $pubs[$game->id] : NULL;
					}
				}
			}
			return $res;
		}
	}

	private function CreateBoxartFilterQuery($filter, &$is_filter)
	{
		$qry = "";
		switch($filter)
		{
			case 'series':
				$filter = 'banner';
			case 'fanart':
			case 'banner':
			case 'boxart':
			case 'screenshot':
			case 'icon':
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
				$qry .=" type = '$filter' ";
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

		$qry = "Select B.games_id, B.id, B.type, B.side, B.filename, B.resolution FROM banners B, (SELECT id FROM games WHERE id IN ($GameIDs) LIMIT :limit OFFSET :offset) T WHERE B.games_id = T.id ";
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
		$qry .= ";";

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

	function GetGameBoxartTypes()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare('SELECT DISTINCT type, side FROM `banners`;');

		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetLatestGameBoxartStats($limit = 20)
	{
		$type_list = $this->GetGameBoxartTypes();

		$queries = array();
		foreach($type_list as $type)
		{
			$qry = "(Select games_id as game_id, type, side, filename, resolution FROM banners WHERE type = '$type->type'";

			if(!empty($type->side) && ($type->side == 'front' || $type->side == 'back'))
			{
				$qry .= " AND side = '$type->side' ";
			}
			$qry .= " ORDER BY id DESC LIMIT $limit)";
			$queries[] = $qry;
		}

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare(implode(" Union ", $queries));

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetLatestGameBoxart($offset = 0, $limit = 20, $filters = 'boxart', $side = '')
	{
		$qry = "Select games_id as game_id, type, side, filename, resolution FROM banners WHERE 1 ";
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

	function GetPlatformBoxartByID($IDs = 0, $offset = 0, $limit = 20, $filters = 'boxart')
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

		$qry = "Select platforms_id, id, type, filename FROM platforms_images WHERE platforms_id IN ($PlatformIDs) ";
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

	function GetGamesStats()
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("SELECT type, count from statistics WHERE type != 'boxart' AND type NOT LIKE 'plat%' ORDER BY type");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
			{
				$ret =  $dbh->query("select count(id) from games where overview is not null", PDO::FETCH_COLUMN, 0);
				$res['overview'] = $ret->fetch();
			}
			return $res;
		}
	}

	function GetGenres()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("SELECT id as n, id, genre as name FROM genres");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
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

	function GetDevsList()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("SELECT id as n, id, name FROM devs_list order by name");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP| PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetDevsListByIDs($IDs)
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
		$sth = $dbh->prepare("SELECT id as n, id, name FROM devs_list where id IN($devs_IDs) order by name");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP| PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetGamesGenres($games_id, $include_game_id = true)
	{
		if(!empty($games_id) && is_array($games_id))
		{
			foreach($games_id as $key => $val)
			{
				if(is_numeric($val))
				{
					$valid_games_id[] = $val;
				}
			}
			if(!empty($valid_games_id))
			{
				$games_id = implode(",", $valid_games_id);
			}
		}
		else if(empty($games_id) || !is_numeric($games_id))
		{
			return array();
		}
		$dbh = $this->database->dbh;
		$qry = "SELECT games_id as n, genres_id";
		if($include_game_id)
		{
			$qry .= ", games_id";
		}
		$qry .= " FROM games_genre where games_id IN($games_id);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
		}
	}

	function GetGamesDevs($games_id, $include_game_id = true)
	{
		if(!empty($games_id) && is_array($games_id))
		{
			foreach($games_id as $key => $val)
			{
				if(is_numeric($val))
				{
					$valid_games_id[] = $val;
				}
			}
			$games_id = implode(",", $valid_games_id);
		}
		else if(empty($games_id) || !is_numeric($games_id))
		{
			return array();
		}
		$dbh = $this->database->dbh;
		$qry = "SELECT games_id as n, dev_id";
		if($include_game_id)
		{
			$qry .= ", games_id";
		}
		$qry .= " FROM games_devs where games_id IN($games_id);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
		}
	}

	function GetGamesPubs($games_id, $include_game_id = true)
	{
		if(!empty($games_id) && is_array($games_id))
		{
			foreach($games_id as $key => $val)
			{
				if(is_numeric($val))
				{
					$valid_games_id[] = $val;
				}
			}
			$games_id = implode(",", $valid_games_id);
		}
		else if(empty($games_id) || !is_numeric($games_id))
		{
			return array();
		}
		$dbh = $this->database->dbh;
		$qry = "SELECT games_id as n, pub_id";
		if($include_game_id)
		{
			$qry .= ", games_id";
		}
		$qry .= " FROM games_pubs where games_id IN($games_id);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
		}
	}

	function GetUserEdits($condition = '', $order = '', $offset = 0, $limit = 100)
	{
		if(empty($condition))
		{
			die("Error, empty condition.");
		}
		$orderby = "";
		if(!empty($order))
		{
			$orderby = "order by $order";
		}
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select id as edit_id, games_id as game_id, timestamp, type, value FROM user_edits
		WHERE $condition $orderby LIMIT :limit OFFSET :offset");
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			foreach($res as $edit)
			{
				//Note, these listing return a json array of data, thus must be decoded to any array to be encoded correctly later
				switch($edit->type)
				{
					case 'genres':
					case 'developers':
					case 'publishers':
					case 'alternates':
						$edit->value = json_decode($edit->value);
				}
			}
			return $res;
		}
	}

	function GetGamesAlts($games_id, $include_id = true)
	{
		$dbh = $this->database->dbh;
		$Games_IDs;
		if(is_array($games_id))
		{
			if(!empty($games_id))
			{
				foreach($games_id as $key => $val)
					if(is_numeric($val))
						$valid_ids[] = $val;
			}
			$Games_IDs = implode(",", $valid_ids);
		}
		else if(is_numeric($games_id))
		{
			$Games_IDs = $games_id;
		}
		if(empty($Games_IDs))
		{
			return array();
		}
		$qry = "SELECT games_id as n, name";
		if($include_id)
		{
			$qry .= ", id, games_id";
		}
		$qry .= " FROM games_alts where games_id IN($Games_IDs);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
			return $res;
		}
	}

	function GetUserEditsByTime($minutes = 1440, $offset = 0, $limit = 100)
	{

		return $this->GetUserEdits("timestamp > DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -$minutes MINUTE)", "id DESC", $offset, $limit);


		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select id as edit_id, games_id as game_id, timestamp, type, value FROM user_edits
		WHERE timestamp > DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -:minutes MINUTE) order by id LIMIT :limit OFFSET :offset");
		$sth->bindValue(':minutes', $minutes, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ);
		}
	}

	function GetUserEditsByID($id, $offset = 0, $limit = 100)
	{
		return $this->GetUserEdits("id > $id", "", $offset, $limit);

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select id as edit_id, games_id as game_id, timestamp, type, value FROM user_edits
		WHERE id > :id LIMIT :limit OFFSET :offset");
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ);
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

	function GetESRBrating()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("SELECT id as n, id, name FROM ESRB_rating");
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
		}
	}

	//TO SLOW, so instead create a DB that I'd update periodically cron job
	function UpdateStats()
	{
		$dbh = $this->database->dbh;

		$Total = array();
		$sth = $dbh->prepare("SELECT DISTINCT type from banners");
		if($sth->execute())
		{
			$types = $sth->fetchAll(PDO::FETCH_COLUMN);
			foreach($types as $type)
			{
				$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where type = :type and games_id IN (select id from games) GROUP BY games_id");
				$sth->bindValue(':type', $type, PDO::PARAM_STR);
				if($sth->execute())
				{
					$Total[$type] = $sth->rowCount();
				}
			}
			$Total['boxart'] = array();
			$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where type = 'boxart' and side = 'front' and games_id IN (select id from games) GROUP BY games_id");
			if($sth->execute())
			{
				$Total['boxart']['front'] = $sth->rowCount();
			}
			$sth = $dbh->prepare("SELECT 1 as total FROM `banners` where type = 'boxart' and side = 'back' and games_id IN (select id from games) GROUP BY games_id");
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
			$dbh->query("update statistics set count = (select count(id) from games) where type = 'total';");
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

	function InsertUserGameBookmark($users_id, $Game, $is_booked)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("INSERT INTO `user_games` (users_id, games_id, platforms_id, is_booked)
		VALUES (:users_id, :games_id, :platforms_id, :is_booked)
		ON DUPLICATE KEY UPDATE is_booked = :is_booked2");
		$sth->bindValue(':games_id', $Game->id);
		$sth->bindValue(':platforms_id', $Game->platform);
		$sth->bindValue(':users_id', $users_id);
		$sth->bindValue(':is_booked', $is_booked);
		$sth->bindValue(':is_booked2', $is_booked);

		return ($sth->execute());
	}

	function GetUserBookmarkedGamesGroupByPlatform($users_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select G.platform, G.id, G.game_title, G.release_date, G.platform FROM `user_games` UG, `games` G where UG.users_id=:users_id AND UG.is_booked=1 AND G.id = UG.games_id ORDER BY UG.added DESC");
		$sth->bindValue(':users_id', $users_id);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetUserBookmarkedGamesByPlatformID($users_id, $platform_id, $offset = 0, $limit = 18)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select G.platform, G.id, G.game_title, G.release_date, G.platform FROM `user_games` UG, `games` G
		where UG.users_id=:users_id AND UG.is_booked=1 AND G.platform = :platform_id AND G.id = UG.games_id ORDER BY UG.added DESC LIMIT :limit OFFSET :offset");
		
		$sth->bindValue(':users_id', $users_id);
		$sth->bindValue(':platform_id', $platform_id);

		$sth->bindValue(':offset', $offset);
		$sth->bindValue(':limit', $limit);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP);
			return $res;
		}
	}

	function GetUserBookmarkedGames($users_id, $offset = 0, $limit = 18)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select G.id, G.game_title, G.release_date, G.platform FROM `user_games` UG, `games` G where UG.users_id=:users_id AND UG.is_booked=1 AND G.id = UG.games_id  ORDER BY UG.added DESC LIMIT :limit OFFSET :offset");
		$sth->bindValue(':users_id', $users_id);

		$sth->bindValue(':offset', $offset);
		$sth->bindValue(':limit', $limit);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function GetUserBookmarkedGamesPlatforms($users_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select DISTINCT platforms_id FROM `user_games` WHERE users_id=:users_id AND is_booked=1");
		$sth->bindValue(':users_id', $users_id);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_COLUMN);
			return $res;
		}
	}

	function isUserGameBookmarked($users_id, $games_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select is_booked FROM `user_games` where users_id=:users_id AND games_id=:games_id AND is_booked=1");
		$sth->bindValue(':games_id', $games_id);
		$sth->bindValue(':users_id', $users_id);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_ASSOC);
			return !empty($res) ? 1 : 0;
		}
		return 0;
	}

	function GetGameCount($searchTerm)
	{
		$dbh = $this->database->dbh;
		
		$qry = "select count(*) from (select count(id) as count from games where (game_title LIKE :name OR game_title=:name2 OR soundex(game_title) LIKE soundex(:name3)
		OR soundex(game_title) LIKE soundex(:name4)) GROUP BY id) search";
		$sth = $dbh->prepare($qry);
		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");
		if($sth->execute())
		{
			return $sth->fetch(PDO::FETCH_COLUMN);
		}
		return -1;
	}

	function InsertUserEdits($user_id, $game_id, $type, $value, $subtype = '')
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT INTO user_edits (users_id, games_id, type, value) VALUES (:users_id, :games_id, :type, :value);");
		$sth->bindValue(':users_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_INT);
		$sth->bindValue(':value', $value, PDO::PARAM_STR);
		return $sth->execute();
	}

	function InsertGamesAltName($game_id, $alt_name)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_alts (games_id, name, SOUNDEX) VALUES (:games_id, :alt_name, soundex(:alt_name2));");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':alt_name', $alt_name, PDO::PARAM_STR);
		$sth->bindValue(':alt_name2', $alt_name, PDO::PARAM_STR);
		return $sth->execute();
	}

	function DeleteGamesAltName($game_id, $alt_name)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_alts  WHERE games_id=:games_id AND name=:alt_name");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':alt_name', $alt_name, PDO::PARAM_STR);
		return $sth->execute();
	}

	function UpdateGamesAltName($user, $games_id, $new_alt_names)
	{
		$dbh = $this->database->dbh;

		$is_changed = false;
		$valid_alt_name = array();

		$current_alt_names = $this->GetGamesAlts($games_id, false);
		if(!empty($current_alt_names[$games_id]))
		{
			$current_alt_names = $current_alt_names[$games_id];
		}
		if(!empty($new_alt_names))
		{
			foreach($new_alt_names as &$new_alt_name)
			{
				$new_alt_name = trim($new_alt_name);
			}
			unset($new_alt_name);
			foreach($new_alt_names as $new_alt_name)
			{
				if(!empty($new_alt_name))
				{
					$valid_alt_name[] = $new_alt_name;

					if(!in_array($new_alt_name, $current_alt_names, true))
					{
						$res = $this->InsertGamesAltName($games_id, $new_alt_name);
						if(!$dbh->inTransaction() && !$res)
						{
							return false;
						}
						$is_changed = true;
					}
				}
			}
		}

		if(!empty($current_alt_names))
		{
			foreach($current_alt_names as $current_alt_name)
			{
				if(!in_array($current_alt_name, $new_alt_names, true))
				{
					$res = $this->DeleteGamesAltName($games_id, $current_alt_name);
					if(!$dbh->inTransaction() && !$res)
					{
						return false;
					}
					$is_changed = true;
				}
			}
		}

		if($is_changed)
		{
			$valid_alt_name = array_unique($valid_alt_name);
			if(!empty($valid_alt_name))
			{
				$this->InsertUserEdits($user_id, $games_id, "alternates", json_encode($valid_alt_name));
			}
		}
		return true;
	}

	function InsertGamesGenre($game_id, $genres_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_genre (games_id, genres_id) VALUES (:games_id, :genres_id);");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':genres_id', $genres_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function DeleteGamesGenre($game_id, $genres_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_genre  WHERE games_id=:games_id AND genres_id=:genres_id");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':genres_id', $genres_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function UpdateGamesGenre($user_id, $games_id, $new_ids)
	{
		$dbh = $this->database->dbh;
		$is_changed = false;
		$valid_ids = array();

		$list = $this->GetGenres();

		$current_ids = $this->GetGamesGenres($games_id);
		if(isset($current_ids[$games_id]))
		{
			$current_ids = $current_ids[$games_id];
		}
		$valid_ids = array();

		foreach($new_ids as $new_id)
		{
			if(isset($list[$new_id]))
			{
				$valid_ids[] = $new_id;

				if(!in_array($new_id, $current_ids))
				{
					$res = $this->InsertGamesGenre($games_id, $new_id);
					if(!$dbh->inTransaction() && !$res)
					{
						return false;
					}
					$is_changed = true;
				}
			}
		}

		foreach($current_ids as $current_id)
		{
			if(isset($list[$new_id]) && !in_array($current_id, $new_ids))
			{
				$res = $this->DeleteGamesGenre($games_id, $current_id);
				if(!$dbh->inTransaction() && !$res)
				{
					return false;
				}
				$is_changed = true;
			}
		}

		if($is_changed)
		{
			$valid_ids = array_unique($valid_ids);
			if(!empty($valid_ids))
			{
				$this->InsertUserEdits($user_id, $games_id, "genres", json_encode($valid_ids, JSON_NUMERIC_CHECK));
			}
		}
		return true;
	}

	function InsertGamesDev($game_id, $dev_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_devs (games_id, dev_id) VALUES (:games_id, :dev_id);");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':dev_id', $dev_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function DeleteGamesDev($game_id, $dev_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_devs  WHERE games_id=:games_id AND dev_id=:dev_id");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':dev_id', $dev_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function UpdateGamesDev($user_id, $games_id, $new_ids)
	{
		$dbh = $this->database->dbh;

		$is_changed = false;
		$valid_ids = array();

		$list = $this->GetDevsListByIDs($new_ids);
		$current_ids = $this->GetGamesDevs($games_id);
		if(!empty($current_ids[$games_id]))
		{
			$current_ids = $current_ids[$games_id];
		}

		foreach($new_ids as $new_id)
		{
			if(isset($list[$new_id]))
			{
				$valid_ids[] = $new_id;

				if(!in_array($new_id, $current_ids))
				{
					$res = $this->InsertGamesDev($games_id, $new_id);
					if(!$dbh->inTransaction() && !$res)
					{
						return false;
					}
					$is_changed = true;
				}
			}
		}

		foreach($current_ids as $current_id)
		{
			if(isset($list[$new_id]) && !in_array($current_id, $new_ids))
			{
				$res = $this->DeleteGamesDev($games_id, $current_id);
				if(!$dbh->inTransaction() && !$res)
				{
					return false;
				}
				$is_changed = true;
			}
		}

		if($is_changed)
		{
			$valid_ids = array_unique($valid_ids);
			if(!empty($valid_ids))
			{
				$this->InsertUserEdits($user_id, $games_id, "developers", json_encode($valid_ids, JSON_NUMERIC_CHECK));
			}
		}
		return true;
	}

	function InsertGamesPub($game_id, $pub_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_pubs (games_id, pub_id) VALUES (:games_id, :pub_id);");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':pub_id', $pub_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function DeleteGamesPub($game_id, $pub_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_pubs WHERE games_id=:games_id AND pub_id=:pub_id");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':pub_id', $pub_id, PDO::PARAM_INT);
		return $sth->execute();
	}

	function UpdateGamesPub($user_id, $games_id, $new_ids)
	{
		$dbh = $this->database->dbh;

		$is_changed = false;
		$valid_ids = array();

		$list = $this->GetPubsListByIDs($new_ids);
		$current_ids = $this->GetGamesPubs($games_id);
		if(!empty($current_ids[$games_id]))
		{
			$current_ids = $current_ids[$games_id];
		}

		foreach($new_ids as $new_id)
		{
			if(isset($list[$new_id]))
			{
				$valid_ids[] = $new_id;

				if(!in_array($new_id, $current_ids))
				{
					$res = $this->InsertGamesPub($games_id, $new_id);
					if(!$dbh->inTransaction() && !$res)
					{
						return false;
					}
					$is_changed = true;
				}
			}
		}

		foreach($current_ids as $current_id)
		{
			if(isset($list[$new_id]) && !in_array($current_id, $new_ids))
			{
				$res = $this->DeleteGamesPub($games_id, $current_id);
				if(!$dbh->inTransaction() && !$res)
				{
					return false;
				}
				$is_changed = true;
			}
		}

		if($is_changed)
		{
			$valid_ids = array_unique($valid_ids);
			if(!empty($valid_ids))
			{
				$this->InsertUserEdits($user_id, $games_id, "publishers", json_encode($valid_ids, JSON_NUMERIC_CHECK));
			}
		}
		return true;
	}

	function UpdateGame($user_id, $game_id, $game_title, $overview, $youtube, $release_date, $players, $coop, $new_developers, $new_publishers, $new_genres, $ratings, $alternate_names)
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
			$this->UpdateGamesAltName($user_id, $game_id, $alternate_names);

			if(!empty($new_genres))
			{
				$this->UpdateGamesGenre($user_id, $game_id, $new_genres);
			}
			$rating = "";
			$ratingsList = $this->GetESRBrating();
			{
				if(isset($ratingsList[$ratings]))
				{
					$rating = $ratingsList[$ratings]->name;
				}
			}

			$valid_devs_id = array();
			$developer = "";
			$devs_list = $this->GetDevsListByIDs($new_developers);
			foreach($new_developers as $dev_id)
			{
				if(isset($devs_list[$dev_id]))
				{
					$valid_devs_id[] = $devs_list[$dev_id]->name;
				}
			}
			if(!empty($valid_devs_id))
			{
				$this->UpdateGamesDev($user_id, $game_id, $new_developers);
				$developer = implode(" | ", $valid_devs_id);
			}

			$valid_pubs_id = array();
			$pubs_list = $this->GetPubsListByIDs($new_publishers);
			foreach($new_publishers as $pub_id)
			{
				if(isset($pubs_list[$pub_id]))
				{
					$valid_pubs_id[] = $pubs_list[$pub_id]->name;
				}
			}
			if(!empty($valid_pubs_id))
			{
				$this->UpdateGamesPub($user_id, $game_id, $new_publishers);
			}

			$dbh->beginTransaction();

			$sth = $dbh->prepare("UPDATE games SET game_title=:game_title, overview=:overview, release_date=:release_date, players=:players,
			coop=:coop, youtube=:YouTube, rating=:rating WHERE id=:game_id");
			$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);
			$sth->bindValue(':game_title', htmlspecialchars($game_title), PDO::PARAM_STR);
			$sth->bindValue(':overview', htmlspecialchars($overview), PDO::PARAM_STR);
			$sth->bindValue(':release_date', $release_date, PDO::PARAM_STR);
			$sth->bindValue(':players', $players, PDO::PARAM_INT);
			$sth->bindValue(':YouTube', htmlspecialchars($youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);
			$sth->bindValue(':rating', $rating, PDO::PARAM_STR);

			$sth->execute();
			{
				foreach($Game as $key => $value)
				{
					if(isset($$key) && htmlspecialchars($$key) != $value)
					{
						$diff = htmlspecialchars($$key);
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

		$sth = $dbh->prepare("DELETE FROM banners WHERE id=:id and games_id=:games_id;");
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
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

		$sth = $dbh->prepare("DELETE FROM banners WHERE games_id=:game_id;");
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

		$sth = $dbh->prepare("INSERT INTO banners (games_id, type, side, filename, userid) VALUES (:games_id, :type, :side, :filename, :user_id); ");
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_STR);
		$sth->bindValue(':side', $side, PDO::PARAM_STR);
		$sth->bindValue(':filename', $filename, PDO::PARAM_STR);
		$res = $sth->execute();

		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $game_id, $type, $filename);
		}
		return ($dbh->inTransaction() || $res);
	}

	function DeleteGame($user_id, $games_id)
	{
		$this->DeleteAllGameImages($user_id, $games_id);

		$dbh = $this->database->dbh;

		$dbh->beginTransaction();

		$sth = $dbh->prepare("DELETE FROM games_pubs WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games_genre WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games_devs WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games WHERE id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();
		if($dbh->inTransaction() || $res)
		{
			$this->InsertUserEdits($user_id, $games_id, "game", "[REMOVED]");
		}

		return $dbh->commit();
	}

	function InsertGame($user_id, $game_title, $overview, $youtube, $release_date, $players, $coop, $new_developers, $new_publishers, $platform, $new_genres, $ratings, $alternate_names)
	{
		$game_id = 0;
		$dbh = $this->database->dbh;
		{
			$rating = "";
			$ratingsList = $this->GetESRBrating();
			{
				if(isset($ratingsList[$ratings]))
				{
					$rating = $ratingsList[$ratings]->name;
				}
			}

			$valid_devs_id = array();
			$developer = "";
			$devs_list = $this->GetDevsList();
			foreach($new_developers as $dev_id)
			{
				if(isset($devs_list[$dev_id]))
				{
					$valid_devs_id[] = $devs_list[$dev_id]->name;
				}
			}
			if(!empty($valid_devs_id))
			{
				$developer = implode(" | ", $valid_devs_id);
			}

			$valid_pubs_id = array();
			$pubs_list = $this->GetPubsList();
			foreach($new_publishers as $pub_id)
			{
				if(isset($pubs_list[$pub_id]))
				{
					$valid_pubs_id[] = $pubs_list[$pub_id]->name;
				}
			}
			$sth = $dbh->prepare("INSERT INTO games(game_title, overview, release_date, players, coop, youtube, platform, rating)
			values (:game_title, :overview, :release_date, :players, :coop, :youtube, :platform, :rating)");
			$sth->bindValue(':game_title', htmlspecialchars($game_title), PDO::PARAM_STR);
			$sth->bindValue(':overview', htmlspecialchars($overview), PDO::PARAM_STR);
			$sth->bindValue(':release_date', $release_date, PDO::PARAM_STR);
			$sth->bindValue(':players', $players, PDO::PARAM_INT);
			$sth->bindValue(':youtube', htmlspecialchars($youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);
			$sth->bindValue(':platform', $platform, PDO::PARAM_INT);
			$sth->bindValue(':rating', $rating, PDO::PARAM_STR);

			if($sth->execute())
			{
				$game_id = $dbh->lastInsertId();
				$dbh->beginTransaction();
				$this->InsertUserEdits($user_id, $game_id, 'game', '[NEW]');

				if(!empty($new_genres))
				{
					$this->UpdateGamesGenre($user_id, $game_id, $new_genres);
				}

				if(!empty($new_developers))
				{
					$this->UpdateGamesDev($user_id, $game_id, $new_developers);
				}

				if(!empty($new_publishers))
				{
					$this->UpdateGamesPub($user_id, $game_id, $new_publishers);
				}

				if(!empty($alternate_names))
				{
					$this->UpdateGamesAltName($user_id, $game_id, $alternate_names);
				}

				$GameArrayFields = ['game_title', 'overview', 'release_date', 'players', 'coop', 'youtube', 'platform', 'rating'];
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

	function GetGamesReports($is_resolved, $offset = 0, $limit = 20)
	{
		$qry = "SELECT games_reports.*, games.game_title, games.platform FROM games_reports left join games on games_reports.games_id = games.id where games_reports.is_resolved = :is_resolved LIMIT :limit OFFSET :offset;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
		$sth->bindValue(':is_resolved', $is_resolved, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

	function ReportGame($user_id, $username, $REQUEST)
	{
		$dbh = $this->database->dbh;
		{
			$sth = $dbh->prepare("Select * FROM games WHERE id = :game_id");
			$sth->bindValue(':game_id', $REQUEST['game_id'], PDO::PARAM_INT);

			if($sth->execute())
			{
				$Game = $sth->fetch(PDO::FETCH_ASSOC);
			}
			if(!isset($Game) || empty($Game))
			{
				return -1;
			}
		}
		if($REQUEST['report_type'] == 1)
		{
			$sth = $dbh->prepare("Select * FROM games WHERE id = :game_id");
			$sth->bindValue(':game_id', $REQUEST['metadata_0'], PDO::PARAM_INT);

			if($sth->execute())
			{
				$Game = $sth->fetch(PDO::FETCH_ASSOC);
			}
			if(!isset($Game) || empty($Game))
			{
				return -2;
			}
		}

		$qry = "INSERT INTO games_reports (user_id, username, games_id, type, metadata_0, extra, is_resolved) values (:user_id, :username, :games_id, :type, :metadata_0, :extra, 0)";

		$sth = $dbh->prepare($qry);

		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);

		$sth->bindValue(':games_id', $REQUEST['game_id'], PDO::PARAM_INT);

		$sth->bindValue(':type',  $REQUEST['report_type'], PDO::PARAM_INT);
		$sth->bindValue(':metadata_0',  !empty($REQUEST['metadata_0']) ? $REQUEST['metadata_0'] : null, PDO::PARAM_STR);
		$sth->bindValue(':extra', !empty($REQUEST['extra']) ? $REQUEST['extra'] : null, PDO::PARAM_STR);

		return $sth->execute();
	}

	function ResolveGameReport($user_id, $username, $id)
	{
		$qry = "UPDATE games_reports SET is_resolved = 1, resolver_user_id=:user_id, resolver_username=:username WHERE id=:id;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}
}

?>
