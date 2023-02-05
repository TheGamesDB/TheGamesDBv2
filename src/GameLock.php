<?php

namespace TheGamesDB;

use PDO;

class GameLock
{
	private $_data = array();
	private $_dbh = null;
	private $_is_dirty = false;
	private $_games_id = null;

	private $_lockableTypes = ['game_title', 'region_id' ,'country_id'];

	public function __construct($games_id, $dbh)
	{
		$this->_dbh = $dbh;
		$this->_games_id = $games_id;

		$qry = "Select type, is_locked FROM games_lock WHERE games_id = :games_id;";
		
		$sth = $dbh->prepare($qry);
		$sth->bindValue(':games_id', $games_id, PDO::PARAM_INT);
		if($sth->execute())
		{
			$res = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
			$this->updateData($res);
			$this->_is_dirty = false;
		}
		// if we need to set any default values,
		// then entry is dirty and needs updating
		foreach($this->_lockableTypes as $type)
		{
			if(!array_key_exists($type, $this->_data))
			{
				$this->_is_dirty |= true;
				$this->_data[$type] = false;
			}
		}
	}

	public function updateData($data)
	{
		foreach($data as $key => $val)
		{
			$this->updateLock($key, $val);
		}
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];

		// by default the item is not locked, if we dont have a db entry for it
		return false;
	}

	public function iterator()
	{
		return $this->_data;
	}

	public function updateLock($name, $value)
	{
		if(array_key_exists($name, $this->_data))
		{
			if($this->_data[$name] == (boolean)$value)
			{
				return;
			}
		}
		$this->_is_dirty |= true;
		$this->_data[$name] = (boolean)$value;
	}

	public function commit()
	{
		if(!$this->_is_dirty)
			return;

		$dbh = $this->_dbh;
		$dbh->beginTransaction();

		$qry = "INSERT INTO games_lock (games_id, type, is_locked) VALUES(:games_id, :type, :lock) ON DUPLICATE KEY UPDATE type=:type2, is_locked=:lock2";
		$sth = $dbh->prepare($qry);

		foreach($this->_data as $type => $lock)
		{
			if(!in_array($type, $this->_lockableTypes))
				continue;

			$sth->bindValue(':games_id', $this->_games_id, PDO::PARAM_INT);
			$sth->bindValue(':type', $type, PDO::PARAM_STR);
			$sth->bindValue(':type2', $type, PDO::PARAM_STR);
			$sth->bindValue(':lock', $lock, PDO::PARAM_INT);
			$sth->bindValue(':lock2', $lock, PDO::PARAM_INT);

			$sth->execute();
		}
		$this->_is_dirty = !$dbh->commit();
	}
}
