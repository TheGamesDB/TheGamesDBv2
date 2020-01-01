<?php

define('IN_PHPBB', true);
$phpbb_root_path = __DIR__ . '/../../../forums.thegamesdb.net/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require_once $phpbb_root_path . 'common.' . $phpEx;
$request->enable_super_globals();

class phpBBUser
{
	private function __construct()
	{
		global $user, $auth;
		$user->session_begin();
		$auth->acl($user->data);
		$user->setup();
		$this->user = $user;
		$this->auth = $auth;
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

	function Login($user, $pass)
	{
		global $config, $phpbb_root_path, $phpEx;
		$ret = $this->auth->login($user, $pass);
		if($ret['status'] ==  LOGIN_ERROR_ATTEMPTS)
		{
			$ret['error_msg_str'] = "You exceeded the maximum allowed number of login attempts. In addition to your username and password you now also have to solve the CAPTCHA," .
				"<br/> CAPTCHA login can only be performed through the forums login.<br/>" .
				"You will be automatically redirect to forum login, if it takes longer than 10 seconds <a href='https://forums.thegamesdb.net/ucp.php?mode=login'>Click Here</a>." .
				'<script type="text/javascript">setTimeout(function(){window.location="https://forums.thegamesdb.net/ucp.php?mode=login";}, 8000);</script>';
		}
		elseif($ret['status'] != LOGIN_SUCCESS)
		{
			$ret['error_msg_str'] = sprintf(
				$this->user->lang[$ret['error_msg']],
				($config['email_enable']) ? '<a href="' . append_sid("https://forums.thegamesdb.net/ucp.$phpEx", 'mode=sendpassword') . '">' : '',
				($config['email_enable']) ? '</a>' : '',
				'<a href="' . phpbb_get_board_contact_link($config, $phpbb_root_path, $phpEx) . '">',
				'</a>'
			);
		}
		return $ret;
	}

	function isLoggedIn()
	{
		return ($this->user->data['is_registered'] && $this->user->data['user_id'] != ANONYMOUS);
	}

	function Logout()
	{
		global $request;
		if($this->user->data['user_id'] != ANONYMOUS && $request->is_set('sid') && $request->variable('sid', '') === $this->user->session_id)
		{
			$this->user->session_kill();
			return true;
		}
		return false;
	}

	function GetUsername()
	{
		return $this->user->data['username'];
	}

	function GetAvatar()
	{
		if(!empty($this->user->data['user_avatar']))
		{
			return "https://forums.thegamesdb.net/download/file.php?avatar=" . $this->user->data['user_avatar'];
		}
	}

	function GetUserID()
	{
		return $this->user->data['user_id'];
	}

	function GetUserSessionID()
	{
		return $this->user->session_id;
	}

	function hasPermission($perm)
	{
		// we're using permission to post in general forum as a permission to edit covers/platform information
		return $this->auth->acl_get($perm) > 0;
	}
}

?>