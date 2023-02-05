<?php

namespace TheGamesDB\Login;

class PseudoUser
{
    private function __construct()
    {

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

    function Login($login_autologin, $login_viewonline)
    {

        $ret = [];
        return $ret;
    }

    function isLoggedIn()
    {
        return true;
    }

    function Logout()
    {
        return false;
    }

    function GetUsername()
    {
        return "PsudoUser";
    }

    function GetAvatar()
    {
        if(!empty($this->user->data['user_avatar']))
        {
            return "https://forums.thegamesdb.net/download/file.php?avatar=48";
        }
    }

    function GetUserID()
    {
        return 48;
    }

    function GetUserSessionID()
    {
        return 0;
    }

    function hasPermission($perm)
    {
        // we're using permission to post in general forum as a permission to edit covers/platform information
        return true;
    }

    function GetNotificationCount()
    {
        return 0;
    }
    
    function GetPMCount()
    {
        return 0;
    }
}
