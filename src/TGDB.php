<?php

namespace TheGamesDB;

use PDO;
use Exception;

class TGDB
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

		if(isset($fields['uids']))
		{
			$uids = $this->GetGamesUIDs($GameIDs, false, true, false);
		}

		if(isset($fields['hashes']))
		{
			$hashes = $this->GetGamesHashes($GameIDs, false);
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
			if(isset($fields['uids']))
			{
				$game->uids = !empty($uids[$game->id]) ? $uids[$game->id] : NULL;
			}
			if(isset($fields['hashes']))
			{
				$game->hashes = !empty($hashes[$game->id]) ? $hashes[$game->id] : NULL;
			}
		}
	}

	function GetGameListByPlatform($IDs = 0, $offset = 0, $limit = 20, $fields = array(), $OrderBy = '', $ASCDESC = 'ASC')
	{
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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

	function GetGames($conditions, $fields = [])
	{

		if(empty($conditions))
			throw new Exception('conditions can not be empty');

		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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

		
		$prepare_sql_conditions = [];
		foreach($conditions as $key => $ignored)
		{
			if($key && $this->is_valid_games_col($key))
			{
				$prepare_sql_conditions[] = "$key=:$key";
			}
		}
		if(empty($prepare_sql_conditions))
			throw new Exception('No Valid Conditions Provided');

		$condition = implode(" AND ", $prepare_sql_conditions);
		$qry .= "Where " . $condition . ";";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);

		foreach($conditions as $key => $data)
		{
			if($key && $this->is_valid_games_col($key))
			{
				$value = $data[0];
				$type = $data[1];
				$sth->bindValue(":$key", $value, $type);
			}
		}

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
		return;
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

		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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

		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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

	function SearchGamesByNameFilter($game_title, $filters, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry_filters = [];
		if(!empty($filters))
		{
			$filters = array_filter($filters, function($key) {
				return in_array($key, ['platform', 'region_id', 'country_id']);
			}, ARRAY_FILTER_USE_KEY);

			foreach($filters as $key => $filter)
			{
				if(is_array($filter))
				{
					$tmp = array();
					if(!empty($filter))
					{
						foreach($filter as $key1 => $val)
							if(is_numeric($val))
								$tmp[] = $val;
					}
					$qry_filters[$key] = implode(",", $tmp);
				}
				else if(!empty($filter))
				{
					$qry_filters[$key] = $filter;
				}
			}
		}

		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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

		foreach($qry_filters as $key => $filter)
		{
			$qry .= " AND games.$key IN ($filter) ";
		}

		$qry .= "GROUP BY games.id ORDER BY MIN(games_ordered.rank) LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$searchTerm = htmlspecialchars($game_title);
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

	function SearchGamesByNameFilter_Natural($game_title, $filters, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$qry_filters = [];
		if(!empty($filters))
		{
			$filters = array_filter($filters, function($key) {
				return in_array($key, ['platform', 'region_id', 'country_id']);
			}, ARRAY_FILTER_USE_KEY);

			foreach($filters as $key => $filter)
			{
				if(is_array($filter))
				{
					$tmp = array();
					if(!empty($filter))
					{
						foreach($filter as $key1 => $val)
							if(is_numeric($val))
								$tmp[] = $val;
					}
					$qry_filters[$key] = implode(",", $tmp);
				}
				else if(!empty($filter))
				{
					$qry_filters[$key] = $filter;
				}
			}
		}

		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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
			SELECT games_names_merged.id, games_names_merged.score, @rank := @rank + 1 AS rank FROM
			(
				(
					SELECT games_id as id, name as game_title, MATCH (name) AGAINST (:name IN NATURAL LANGUAGE MODE) as score from games_alts
					WHERE  MATCH (name) AGAINST (:name2 IN NATURAL LANGUAGE MODE) > 0
				)
				UNION
				(
					SELECT id, game_title, MATCH (game_title) AGAINST (:name3 IN NATURAL LANGUAGE MODE) as score from games
					WHERE MATCH (game_title) AGAINST (:name4 IN NATURAL LANGUAGE MODE) > 0
				)
			) games_names_merged, (SELECT @rank := 0) t1
			ORDER BY CASE
				WHEN game_title like :name_3 THEN 0
				WHEN game_title like :name_3_1 THEN 1
				WHEN game_title like :name_3_2 THEN 2
				WHEN game_title like :name_3_3 THEN 3
				ELSE 4
				END, game_title
		) games_ordered where games.id = games_ordered.id ";

		foreach($qry_filters as $key => $filter)
		{
			$qry .= " AND games.$key IN ($filter) ";
		}

		$qry .= "GROUP BY games.id ORDER BY MIN(games_ordered.rank) LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$searchTerm = htmlspecialchars($game_title);
		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name_3', $searchTerm);
		$sth->bindValue(':name_3_1', "$searchTerm%");
		$sth->bindValue(':name_3_2', "% %$searchTerm% %");
		$sth->bindValue(':name_3_3', "%$searchTerm");

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

	function SearchGamesByNameByPlatformID($game_title, $IDs, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$PlatformIDs = array();
		if(is_array($IDs))
		{
			$PlatformIDsArr = array();
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

		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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

		$searchTerm = htmlspecialchars($game_title);
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

	function SearchGamesByName_Natural($searchTerm, $offset = 0, $limit = 20, $fields = array())
	{
		return $this->SearchGamesByNameByPlatformID_Natural($searchTerm, '', $offset, $limit, $fields);
	}

	function SearchGamesByNameByPlatformID_Natural($game_title, $IDs, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$PlatformIDs = array();
		if(is_array($IDs))
		{
			$PlatformIDsArr = array();
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

		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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
			SELECT games_names_merged.id, games_names_merged.score, @rank := @rank + 1 AS rank FROM
			(
				(
					SELECT games_id as id, name as game_title, MATCH (name) AGAINST (:name IN NATURAL LANGUAGE MODE) as score from games_alts
					WHERE  MATCH (name) AGAINST (:name2 IN NATURAL LANGUAGE MODE) > 0
				)
				UNION
				(
					SELECT id, game_title, MATCH (game_title) AGAINST (:name3 IN NATURAL LANGUAGE MODE) as score from games
					WHERE MATCH (game_title) AGAINST (:name4 IN NATURAL LANGUAGE MODE) > 0
				)
			) games_names_merged, (SELECT @rank := 0) t1
			ORDER BY CASE
				WHEN game_title like :name_3 THEN 0
				WHEN game_title like :name_3_1 THEN 1
				WHEN game_title like :name_3_2 THEN 2
				WHEN game_title like :name_3_3 THEN 3
				ELSE 4
				END, game_title
		) games_ordered where games.id = games_ordered.id ";
		if(!empty($PlatformIDs))
		{
			$qry .= " AND games.platform IN ($PlatformIDs) ";
		}
		$qry .= "GROUP BY games.id ORDER BY MIN(games_ordered.rank) LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);

		$searchTerm = htmlspecialchars($game_title);
		$sth->bindValue(':name', "%$searchTerm%");
		$sth->bindValue(':name2', $searchTerm);
		$sth->bindValue(':name3', "$searchTerm%");
		$sth->bindValue(':name4', "% %$searchTerm% %");

		$sth->bindValue(':name_3', $searchTerm);
		$sth->bindValue(':name_3_1', "$searchTerm%");
		$sth->bindValue(':name_3_2', "% %$searchTerm% %");
		$sth->bindValue(':name_3_3', "%$searchTerm");

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

	function SearchGamesByUniqueID($games_uid_unfiltered, $offset = 0, $limit = 20, $fields = array())
	{
		return $this->SearchGamesByUniqueIDByPlatformID($games_uid_unfiltered, 0, $offset, $limit, $fields);
	}

	function SearchGamesByUniqueIDByPlatformID($games_uid_unfiltered, $PlatformIDs_unfiltered, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$PlatformIDs = array();
		if(is_array($PlatformIDs_unfiltered))
		{
			$PlatformIDsArr = array();
			if(!empty($PlatformIDs_unfiltered))
			{
				foreach($PlatformIDs_unfiltered as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
		}
		else if(is_numeric($PlatformIDs_unfiltered) && $PlatformIDs_unfiltered > 0)
		{
			$PlatformIDs = $PlatformIDs_unfiltered;
		}

		if(!empty($games_uid_unfiltered))
		{
			$games_uid_arr = explode(",", $games_uid_unfiltered);
			$valid_chars = "abcdefghijklmnopqrstuvwxyz1234567890-_.";
			$games_uid_arr_filter = [];
			foreach($games_uid_arr as $uid)
			{
				$i = 0;
				while($i < strlen($uid))
				{
					$j = 0;
					while($j < strlen($valid_chars))
					{
						if(strtolower($uid[$i]) == $valid_chars[$j])
						{
							// Valid
							break;
						}
						++$j;
					}
					if($j == strlen($valid_chars))
					{
						// Invalid
						break;
					}
					++$i;
				}
				if($i == strlen($uid))
				{
					$games_uid_arr_filter[] = $uid;
				}
			}
			if(!empty($games_uid_arr_filter))
			{
				$games_uids = "\"" . implode("\",\"", $games_uid_arr_filter) . "\"";
			}
			else
			{
				return array();
			}
		}
		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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
		$qry .= " FROM games, games_uids where games.id = games_uids.games_id and games_uids.uid in ($games_uids) ";
		if(!empty($PlatformIDs))
		{
			$qry .= " AND games.platform IN ($PlatformIDs) ";
		}
		$qry .= " LIMIT :limit OFFSET :offset";

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

	function SearchGamesByHashByPlatformID($games_hash_unfiltered, $PlatformIDs_unfiltered, $filter_type, $offset = 0, $limit = 20, $fields = array())
	{
		$dbh = $this->database->dbh;

		$PlatformIDs = array();
		if(is_array($PlatformIDs_unfiltered))
		{
			$PlatformIDsArr = array();
			if(!empty($PlatformIDs_unfiltered))
			{
				foreach($PlatformIDs_unfiltered as $key => $val)
					if(is_numeric($val))
						$PlatformIDsArr[] = $val;
			}
			$PlatformIDs = implode(",", $PlatformIDsArr);
		}
		else if(is_numeric($PlatformIDs_unfiltered) && $PlatformIDs_unfiltered > 0)
		{
			$PlatformIDs = $PlatformIDs_unfiltered;
		}

		if(!empty($games_hash_unfiltered))
		{
			$games_hashes_arr = explode(",", $games_hash_unfiltered);
			$valid_chars = "abcdefghijklmnopqrstuvwxyz1234567890";
			$games_hashes_arr_filter = [];
			foreach($games_hashes_arr as $hash)
			{
				$i = 0;
				while($i < strlen($hash))
				{
					$j = 0;
					while($j < strlen($valid_chars))
					{
						if(strtolower($hash[$i]) == $valid_chars[$j])
						{
							// Valid
							break;
						}
						++$j;
					}
					if($j == strlen($valid_chars))
					{
						// Invalid
						break;
					}
					++$i;
				}
				if($i == strlen($hash))
				{
					$games_hash_arr_filter[] = $hash;
				}
			}
			if(!empty($games_hash_arr_filter))
			{
				$games_hashes = "\"" . implode("\",\"", $games_hash_arr_filter) . "\"";
			}
			else
			{
				return array();
			}
		}
		$qry = "Select games.id, games.game_title, games.release_date, games.platform, games.region_id, games.country_id ";

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
		$qry .= " FROM games, games_hashes where games.id = games_hashes.games_id and games_hashes.hashes in ($games_hashes) ";
		if(!empty($filter_type))
		{
			$qry .= " AND games_hashes.hashes = :type";
		}

		if(!empty($PlatformIDs))
		{
			$qry .= " AND games.platform IN ($PlatformIDs) ";
		}
		$qry .= " LIMIT :limit OFFSET :offset";

		$sth = $dbh->prepare($qry);
		if(!empty($filter_type))
		{
			$sth->bindValue(':type', $type, PDO::PARAM_STR);
		}
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
		$qry = "Select id, game_title, release_date, platform, region_id, country_id";

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
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		else if(is_numeric($platform_ids) && $platform_ids != 0)
		{
			$valid_platform_ids = $platform_ids;
		}

		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		else if(is_numeric($platform_ids) && $platform_ids > 0)
		{
			$valid_platform_ids = $platform_ids;
		}
		$qry = "Select id, game_title, release_date, platform, region_id, country_id ";

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
		if(!empty($valid_platform_ids))
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
			case 'titlescreen':
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
		$sth = $dbh->prepare("SELECT id as n, id, genre as name FROM genres order by name");
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function InsertPub($name)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT INTO pubs_list(name) VALUES(:name);");
		$sth->bindValue(':name', $name);

		if($sth->execute())
		{
			return $dbh->lastInsertId();
		}
		return false;
	}

	function GetPubsList($limit = -1)
	{
		$qry = "SELECT id as n, id, name FROM pubs_list order by name";
		if($limit > 0)
		{
			$qry .= " LIMIT $limit;";
		}
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP| PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function InsertDev($name)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT INTO devs_list(name) VALUES(:name);");
		$sth->bindValue(':name', $name);

		if($sth->execute())
		{
			return $dbh->lastInsertId();
		}
		return false;
	}

	function GetDevsList($limit = -1)
	{
		$qry = "SELECT id as n, id, name FROM devs_list order by name";
		if($limit > 0)
		{
			$qry .= " LIMIT $limit;";
		}
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
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
					case 'uids':
					case 'serials':
					case 'hashes':
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

	function GetGamesUIDs($games_id, $include_id = true, $include_type = true, $simple_col = true)
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
		$qry = "SELECT games_id as n, uid";
		if($include_id)
		{
			$qry .= ", id, games_id";
		}
		if($include_type)
		{
			$qry .= ", games_uids_patterns_id";

		}
		$qry .= " FROM games_uids where games_id IN($Games_IDs);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			$flags = PDO::FETCH_OBJ | PDO::FETCH_GROUP;
			if($simple_col)
			{
				$flags |= PDO::FETCH_COLUMN;
			}
			return $sth->fetchAll($flags);
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

	function GetUIDPattern($platform_id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select * FROM `games_uids_patterns` where platform_id = :platform_id");
		$sth->bindValue(':platform_id', $platform_id);

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ);
			return $res;
		}
	}

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

		$sth = $dbh->prepare("Select G.platform, G.id, G.game_title, G.release_date, G.platform, G.region_id, G.country_id FROM `user_games` UG, `games` G where UG.users_id=:users_id AND UG.is_booked=1 AND G.id = UG.games_id ORDER BY UG.added DESC");
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

		$sth = $dbh->prepare("Select G.platform, G.id, G.game_title, G.release_date, G.platform, G.region_id, G.country_id FROM `user_games` UG, `games` G
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

		$sth = $dbh->prepare("Select G.id, G.game_title, G.release_date, G.platform, G.region_id, G.country_id FROM `user_games` UG, `games` G where UG.users_id=:users_id AND UG.is_booked=1 AND G.id = UG.games_id  ORDER BY UG.added DESC LIMIT :limit OFFSET :offset");
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

	function UpdateGamesAltName($user_id, $games_id, $new_alt_names)
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
			$this->InsertUserEdits($user_id, $games_id, "alternates", json_encode($valid_alt_name));
		}
		return true;
	}

	function InsertGamesUID($game_id, $uid, $pattern_id)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_uids (games_id, uid, games_uids_patterns_id) VALUES (:games_id, :uid, :games_uids_patterns_id);");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':uid', $uid, PDO::PARAM_STR);
		$sth->bindValue(':games_uids_patterns_id', $pattern_id, PDO::PARAM_STR);
		return $sth->execute();
	}

	function DeleteGamesUID($game_id, $uid)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_uids  WHERE games_id=:games_id AND uid=:uid");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':uid', $uid, PDO::PARAM_STR);
		return $sth->execute();
	}

	function UpdateGamesUID($user_id, $games_id, $platform, $new_uids)
	{
		$dbh = $this->database->dbh;
		$patterns = $this->GetUIDPattern($platform);

		$is_changed = false;
		$valid_uid = array();

		$current_uids = $this->GetGamesUIDs($games_id, false, false);
		if(!empty($current_uids[$games_id]))
		{
			$current_uids = $current_uids[$games_id];
		}
		if(!empty($new_uids))
		{
			foreach($new_uids as &$new_uid)
			{
				$new_uid = trim($new_uid);
			}
			unset($new_uid);
			foreach($new_uids as $new_uid)
			{
				if(!empty($new_uid))
				{
					$pattern_id = 0;
					foreach($patterns as $pattern)
					{
						if(preg_match_all("/$pattern->regex_pattern/", $new_uid, $matches))
						{
							if(count($matches[0]) == 1 && $matches[0][0] == $new_uid)
							{
								$pattern_id = $pattern->id;
								break;
							}
						}
					}
					$valid_uid[] = ["uid" => $new_uid, "games_uids_patterns_id" => $pattern_id];

					if(!in_array($new_uid, $current_uids, true))
					{
						$res = $this->InsertGamesUID($games_id, $new_uid, $pattern_id);
						if(!$dbh->inTransaction() && !$res)
						{
							return false;
						}
						$is_changed = true;
					}
				}
			}
		}

		if(!empty($current_uids))
		{
			foreach($current_uids as $current_uid)
			{
				if(!in_array($current_uid, $new_uids, true))
				{
					$res = $this->DeleteGamesUID($games_id, $current_uid);
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
			$valid_uid = $this->array_unique_hashes($valid_uid, ["uid", "games_uids_patterns_id"]);
			$this->InsertUserEdits($user_id, $games_id, "uids", json_encode($valid_uid));
		}
		return true;
	}

	function GetGamesHashes($games_id, $include_id = true)
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
		$qry = "SELECT games_id as n";
		if($include_id)
		{
			$qry .= ", id, games_id";
		}
		$qry .= ", hash, type FROM games_hashes where games_id IN($Games_IDs);";
		$sth = $dbh->prepare($qry);
		if($sth->execute())
		{
			return $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
			return $res;
		}
	}

	function InsertGamesHash($game_id, $hash, $type)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT IGNORE INTO games_hashes (games_id, hash, type) VALUES (:games_id, :hash, :type);");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':hash', $hash, PDO::PARAM_STR);
		return $sth->execute();
	}

	function DeleteGamesHash($game_id, $hash)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("DELETE FROM games_hashes  WHERE games_id=:games_id AND hash=:hash");
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		$sth->bindValue(':hash', $hash, PDO::PARAM_STR);
		return $sth->execute();
	}

	function array_unique_hashes($arr, $field_names)
	{
		$temp_array =[];
		$key_array = [];

		foreach($arr as $val)
		{
			$keys = array_keys($val);
			break;
		}
		foreach($arr as $val)
		{
			{
				$val_0 = $val[$keys[0]];
				$val_1 = $val[$keys[1]];
				if(!isset($key_array[$val_0]))
				{
					$key_array[$val_0] = [];
				}

				if(!isset($key_array[$val_0][$val_1]))
				{
					$key_array[$val_0][$val_1] = 0;
				}
				$key_array[$val_0][$val_1] += 1;
			}
		}
		foreach($key_array as $val_0 => $subval)
		{
			foreach($subval as $val_1 => $count)
			{
				$temp_array[] =
				[
					$field_names[0] => $val_0,
					$field_names[1] => $val_1
				];
			}
		}
		return $temp_array;
	}

	function UpdateGamesHash($user_id, $games_id, $new_hashes)
	{
		$dbh = $this->database->dbh;

		$is_changed = false;
		$valid_hash = array();

		$current_hashes = $this->GetGamesHashes($games_id, false);
		if(!empty($current_hashes[$games_id]))
		{
			$current_hashes = $current_hashes[$games_id];
		}
		if(!empty($new_hashes))
		{
			foreach($new_hashes as &$new_hash)
			{
				$new_hash = trim($new_hash);
			}
			unset($new_hashl);
			foreach($new_hashes as $new_hash)
			{
				if(!empty($new_hash))
				{
					$type = "";
					foreach(["crc32" => "[a-Z0-9]{8}", "sha1" => "[a-Z0-9]{40}"] as $key => $pattern)
					{
						if(preg_match_all("/$pattern/", $new_hash, $matches))
						{
							if(count($matches[0]) == 1 && $matches[0][0] == $new_hash)
							{
								$type = $key;
								break;
							}
						}
					}
					if(!empty($type))
					{
						$valid_hash[] = ["hash" => $new_hash, "type" => $type];
						if(!in_array($new_hash, $current_hashes, true))
						{
							$res = $this->InsertGamesHash($games_id, $new_hash, $type);
							if(!$dbh->inTransaction() && !$res)
							{
								return false;
							}
							$is_changed = true;
						}
					}
				}
			}
		}

		if(!empty($current_hashes))
		{
			foreach($current_hashes as $current_hash)
			{
				if(!in_array($current_hash, $new_hashes, true))
				{
					$res = $this->DeleteGamesHash($games_id, $current_hash);
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
			$valid_hash = $this->array_unique_hashes($valid_hash, ["hash", "type"]);
			$this->InsertUserEdits($user_id, $games_id, "hashes", json_encode($valid_hash));
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

	function UpdateGame($user_id, $game_id, $game_title, $overview, $youtube, $release_date, $players, $coop, $new_developers, $new_publishers, $new_genres, $ratings, $alternate_names, $uids, $platform, $region_id, $country_id)
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

			$this->UpdateGamesUID($user_id, $game_id, $Game["platform"], $uids);

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
			coop=:coop, youtube=:YouTube, rating=:rating, platform=:platform, region_id=:region_id, country_id=:country_id WHERE id=:game_id");
			$sth->bindValue(':game_id', $game_id, PDO::PARAM_INT);
			$sth->bindValue(':game_title', htmlspecialchars($game_title), PDO::PARAM_STR);
			$sth->bindValue(':overview', htmlspecialchars($overview), PDO::PARAM_STR);
			$sth->bindValue(':release_date', $release_date, PDO::PARAM_STR);
			$sth->bindValue(':players', $players, PDO::PARAM_INT);
			$sth->bindValue(':YouTube', htmlspecialchars($youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);
			$sth->bindValue(':rating', $rating, PDO::PARAM_STR);
			$sth->bindValue(':platform', $platform, PDO::PARAM_INT);

			$sth->bindValue(':region_id', $region_id, PDO::PARAM_INT);
			$sth->bindValue(':country_id', $country_id, PDO::PARAM_INT);

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

		$sth = $dbh->prepare("DELETE FROM games_lock WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games_hashes WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games_alts WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

		$sth = $dbh->prepare("DELETE FROM games_uids WHERE games_id=:games_id;");
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		$res = $sth->execute();

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

	function InsertGame($user_id, $game_title, $overview, $youtube, $release_date, $players, $coop, $new_developers, $new_publishers, $platform, $new_genres, $ratings, $alternate_names, $uids, $region_id, $country_id)
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
			$sth = $dbh->prepare("INSERT INTO games(game_title, overview, release_date, players, coop, youtube, platform, rating, region_id, country_id)
			values (:game_title, :overview, :release_date, :players, :coop, :youtube, :platform, :rating, :region_id, :country_id)");
			$sth->bindValue(':game_title', htmlspecialchars($game_title), PDO::PARAM_STR);
			$sth->bindValue(':overview', htmlspecialchars($overview), PDO::PARAM_STR);
			$sth->bindValue(':release_date', $release_date, PDO::PARAM_STR);
			$sth->bindValue(':players', $players, PDO::PARAM_INT);
			$sth->bindValue(':youtube', htmlspecialchars($youtube), PDO::PARAM_STR);
			$sth->bindValue(':coop', $coop, PDO::PARAM_INT);
			$sth->bindValue(':platform', $platform, PDO::PARAM_INT);
			$sth->bindValue(':rating', $rating, PDO::PARAM_STR);

			$sth->bindValue(':region_id', $region_id, PDO::PARAM_INT);
			$sth->bindValue(':country_id', $country_id, PDO::PARAM_INT);

			if($sth->execute())
			{
				$game_id = $dbh->lastInsertId();
				$dbh->beginTransaction();
				$this->InsertUserEdits($user_id, $game_id, 'game', '[NEW]');

				$GameArrayFields = ['platform', 'game_title', 'overview', 'release_date', 'players', 'coop', 'youtube', 'rating', 'region_id', 'country_id'];
				foreach($GameArrayFields as $key)
				{
					$diff = htmlspecialchars($$key);
					$this->InsertUserEdits($user_id, $game_id, $key, $diff);
				}

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

				if(!empty($uids))
				{
					$this->UpdateGamesUID($user_id, $game_id, $platform, $uids);
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

	function GetGameEditContributors($game_id)
	{
		$qry = "SELECT UE.id, UE.timestamp, UE.type, UE.value, UE.users_id, BB.username FROM user_edits UE, phpbb_0.phpbb_users BB WHERE UE.games_id = :games_id and BB.user_id = UE.users_id order by UE.id DESC;";

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare($qry);
		$sth->bindValue(':games_id', $game_id, PDO::PARAM_INT);
		if($sth->execute())
		{
				$res = $sth->fetchAll(PDO::FETCH_OBJ);
				return $res;
		}
	}

	function GetUserEditsByUserID($user, $offset = 0, $limit = 100)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select * from games where id IN (Select games_id FROM user_edits
		WHERE users_id = :user_id) LIMIT :limit OFFSET :offset");
		$sth->bindValue(':user_id', $user, PDO::PARAM_INT);
		$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
		$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
		if($sth->execute())
		{
				return $sth->fetchAll(PDO::FETCH_OBJ);
		}
	}

	function GetUserEditsCountByUserID($user)
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select count(DISTINCT games_id) FROM user_edits
		WHERE users_id = :user_id and games_id in (SELECT id from games)");
		$sth->bindValue(':user_id', $user, PDO::PARAM_INT);
		if($sth->execute())
		{
			return $sth->fetch(PDO::FETCH_COLUMN);
		}
	}

	function GetLastUserEditID()
	{
		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("Select id FROM user_edits order by id DESC limit 1");
		if($sth->execute())
		{
			return $sth->fetch(PDO::FETCH_COLUMN);
		}
	}

	function GetLegacyCopy($id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("SELECT GameTitle as game_title, Players as players, ReleaseDate as release_date, Developer as developer, Publisher as publisher, Genre as genre,
		Overview as overview, Platform as platform, coop, Youtube as youtube, Alternates as alternates, username as lastupdatedby
		from games_legacy
		left join users on updatedby = users.id
		where games_legacy.id = :games_id limit 1");
		$sth->bindValue(':games_id', $id);

		if($sth->execute())
		{
				return $sth->fetch(PDO::FETCH_OBJ);
		}
	}

	function InsertPlatformImage($user_id, $platforms_id, $type, $filename)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("INSERT INTO platforms_images(platforms_id , type, filename, userid)
		VALUES (:platforms_id, :type, :filename, :user_id); ");
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->bindValue(':platforms_id', $platforms_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_STR);
		$sth->bindValue(':filename', $filename, PDO::PARAM_STR);

		return $sth->execute();
	}

	function deletePlatformImage($platforms_id, $type)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("DELETE FROM platforms_images WHERE platforms_id=:platforms_id and type=:type;");
		$sth->bindValue(':platforms_id', $platforms_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_STR);

		return $sth->execute();
	}

	function InsertPlatform($name, $developer, $manufacturer, $media, $cpu, $memory, $graphics, $sound, $maxcontrollers, $display, $overview, $youtube)
	{
		$alias = str_replace(" ", "-", strtolower($name));
		$alias = str_replace(".", "-", $alias);

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("INSERT INTO platforms(name, alias, developer, manufacturer, media, cpu, memory, graphics, sound, maxcontrollers, display, overview, youtube)
		values (:name, :alias, :developer, :manufacturer, :media, :cpu, :memory, :graphics, :sound, :maxcontrollers, :display, :overview, :youtube)");

		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':alias', $alias, PDO::PARAM_STR);
		// unused
		// $sth->bindValue(':icon', $icon, PDO::PARAM_STR);
		// $sth->bindValue(':console', $console, PDO::PARAM_STR);
		// $sth->bindValue(':controller', $controller, PDO::PARAM_STR);
		$sth->bindValue(':developer', $developer, PDO::PARAM_STR);
		$sth->bindValue(':manufacturer', $manufacturer, PDO::PARAM_STR);
		$sth->bindValue(':media', $media, PDO::PARAM_STR);
		$sth->bindValue(':cpu', $cpu, PDO::PARAM_STR);
		$sth->bindValue(':memory', $memory, PDO::PARAM_STR);
		$sth->bindValue(':graphics', $graphics, PDO::PARAM_STR);
		$sth->bindValue(':sound', $sound, PDO::PARAM_STR);
		$sth->bindValue(':maxcontrollers', $maxcontrollers, PDO::PARAM_INT);
		$sth->bindValue(':display', $display, PDO::PARAM_STR);
		$sth->bindValue(':overview', $overview, PDO::PARAM_STR);
		$sth->bindValue(':youtube', $youtube, PDO::PARAM_STR);


		if($sth->execute())
		{
			return $dbh->lastInsertId();
		}
		return -1;
	}

	function updatePlatform($id, $name, $developer, $manufacturer, $media, $cpu, $memory, $graphics, $sound, $maxcontrollers, $display, $overview, $youtube)
	{
		$alias = str_replace(" ", "-", strtolower($name));
		$alias = str_replace(".", "-", $alias);

		$dbh = $this->database->dbh;
		$sth = $dbh->prepare("UPDATE platforms SET name=:name, alias=:alias, developer=:developer, manufacturer=:manufacturer, media=:media, cpu=:cpu, memory=:memory, graphics=:graphics, sound=:sound, maxcontrollers=:maxcontrollers, display=:display, overview=:overview, youtube=:youtube
		where id=:id;");

		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':alias', $alias, PDO::PARAM_STR);
		// unused
		// $sth->bindValue(':icon', $icon, PDO::PARAM_STR);
		// $sth->bindValue(':console', $console, PDO::PARAM_STR);
		// $sth->bindValue(':controller', $controller, PDO::PARAM_STR);
		$sth->bindValue(':developer', $developer, PDO::PARAM_STR);
		$sth->bindValue(':manufacturer', $manufacturer, PDO::PARAM_STR);
		$sth->bindValue(':media', $media, PDO::PARAM_STR);
		$sth->bindValue(':cpu', $cpu, PDO::PARAM_STR);
		$sth->bindValue(':memory', $memory, PDO::PARAM_STR);
		$sth->bindValue(':graphics', $graphics, PDO::PARAM_STR);
		$sth->bindValue(':sound', $sound, PDO::PARAM_STR);
		$sth->bindValue(':maxcontrollers', $maxcontrollers, PDO::PARAM_INT);
		$sth->bindValue(':display', $display, PDO::PARAM_STR);
		$sth->bindValue(':overview', $overview, PDO::PARAM_STR);
		$sth->bindValue(':youtube', $youtube, PDO::PARAM_STR);


		return $sth->execute();
	}

	function GetRegionsList()
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select id as n, id, name FROM regions;");

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetGameRegion($id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select id, name FROM regions where id=:id;");
		$sth->bindValue(':id', $id, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetCountriesList()
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select id as n, id, name FROM countries order by name;");

		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetGameCountry($id)
	{
		$dbh = $this->database->dbh;

		$sth = $dbh->prepare("Select id, name FROM countries where id=:id;");
		$sth->bindValue(':id', $id, PDO::PARAM_INT);

		if($sth->execute())
		{
			$res = $sth->fetch(PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
			return $res;
		}
	}

	function GetGameLockByID($game_id)
	{
		$qry = "Select type, is_locked FROM games_lock WHERE games_id = :games_id;";
		
		$dbh = $this->database->dbh;
		$Lock = new GameLock($game_id, $dbh);
		return $Lock;
	}
}
