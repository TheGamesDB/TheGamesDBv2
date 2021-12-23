<?php

require_once __DIR__ . "/../../include/config.class.php";
if(Config::$debug)
{
	require __DIR__ . "/login.pseudo.class.php";
}
else
{
	require __DIR__ . "/login.phpbb.class.php";
}
