<?php

use TheGamesDB\Footer;
use TheGamesDB\Config;
use TheGamesDB\Login\PhpBBUser;
use TheGamesDB\Login\PseudoUser;

session_start();

Footer::$_time_start = microtime(true);

global $_user;

if(Config::$debug)
{
    $_user = PseudoUser::getInstance();

    function append_sid($a, $b, $c, $d)
    {
        return '#';
    }
}
else
{
    define('IN_PHPBB', true);
    $phpbb_root_path = __DIR__ . '/../../../forums.thegamesdb.net/';
    $phpEx = substr(strrchr(__FILE__, '.'), 1);
    require_once $phpbb_root_path . 'common.' . $phpEx;
    $request->enable_super_globals();

    $_user = PhpBBUser::getInstance();
}
