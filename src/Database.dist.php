<?php

namespace TheGamesDB;

use PDO;

class Database
{
	private $_dsn = 'mysql:host=localhost;dbname=TGDB;charset=utf8';
	private $_username = "TheGamesDB";
	private $_password = 'XXXXXXXXXXXXX';

	public $dbh;

	public function __construct()
	{
		$this->dbh = new PDO($this->_dsn, $this->_username, $this->_password);
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
}
