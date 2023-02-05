<?php

namespace TheGamesDB\Login;

class PhpBBUser
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

	function Login($login_autologin, $login_viewonline)
	{
		global $config, $phpbb_root_path, $phpEx, $request;
		$login_username = $request->variable('username', '', true, \phpbb\request\request_interface::POST);
		$login_password = $request->untrimmed_variable('password', '', true, \phpbb\request\request_interface::POST);

		$ret = $this->auth->login($login_username, $login_password);
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
		elseif($ret['status'] == LOGIN_SUCCESS)
		{
			$this->user->session_create($ret['user_row']['user_id'], false, $login_autologin, $login_viewonline);
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

	function GetNotificationCount()
	{
			global $phpbb_container;
			$phpbb_notifications = $phpbb_container->get('notification_manager');

			$notifications = $phpbb_notifications->load_notifications('notification.method.board', array(
			'all_unread'	=> true,
			'limit'			=> 5,
			));
			return ($notifications !== false) ? $notifications['unread_count'] : 0;
	}
	
	function GetPMCount()
	{
			return $this->user->data['user_unread_privmsg'];
	}

	function hasPermission($perm)
	{
		// we're using permission to post in general forum as a permission to edit covers/platform information
		return $this->auth->acl_get($perm) > 0;
	}
}
