<?php
require_once __DIR__ . "/include/login.common.class.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";

$error_msgs = array();
$success_msg = array();

$_user = phpBBUser::getInstance();
if(isset($_REQUEST['logout']))
{
	if($_user->isLoggedIn() && $_user->Logout())
	{
		$success_msg[] = "User logged out successfully. You will be automatically redirected, if it takes longer than 10 seconds <a href='" . CommonUtils::$WEBSITE_BASE_URL . "'>Click Here</a>." .
		'<script type="text/javascript">setTimeout(function(){window.location="' . CommonUtils::$WEBSITE_BASE_URL . '";}, 5000);</script>';
	}
	else
	{
		$error_msgs[] = "User is already logged out. You will be automatically redirected, if it takes longer than 10 seconds <a href='" . CommonUtils::$WEBSITE_BASE_URL . "'>Click Here</a>." .
			'<script type="text/javascript">setTimeout(function(){window.location="' . CommonUtils::$WEBSITE_BASE_URL . '";}, 5000);</script>';
	}
}
else if($_user->isLoggedIn())
{
	$error_msgs[] = "User is already logged in. You will be automatically redirected, if it takes longer than 10 seconds <a href='" . CommonUtils::$WEBSITE_BASE_URL . "'>Click Here</a>." .
		'<script type="text/javascript">setTimeout(function(){window.location="' . CommonUtils::$WEBSITE_BASE_URL . '";}, 5000);</script>';
}

if($_SERVER['REQUEST_METHOD'] == "POST" && empty($error_msgs) && empty($success_msg))
{
	if(!$_user->isLoggedIn())
	{
		if(!empty($_POST['username']) && !empty($_POST['password']))
		{
			$res = $_user->Login(isset($_POST['autologin']), isset($_POST['viewonline']));
			if($res['status'] == LOGIN_SUCCESS)
			{
				if(!empty($_POST['redirect']) && strpos($_POST['redirect'], "login") === false)
				{
					$length = strlen("thegamesdb.net");
					$url = parse_url($_POST['redirect']);
					if($length !== 0 && (substr($url['host'], -$length) === "thegamesdb.net"))
					{
						$success_msg[] = "Login successful, You will be automatically redirected, if it takes longer than 10 seconds <a href='" .$_POST['redirect'] . "'>Click Here</a>." .
							'<script type="text/javascript">setTimeout(function(){window.location="' . $_POST['redirect'] . '";}, 5000);</script>';

					}
					else
					{
						$success_msg[] = "Login successful, You will be automatically redirected, if it takes longer than 10 seconds <a href='" . CommonUtils::$WEBSITE_BASE_URL . "'>Click Here</a>." .
							'<script type="text/javascript">setTimeout(function(){window.location="' . CommonUtils::$WEBSITE_BASE_URL . '";}, 5000);</script>';					}
				}
				else
				{
					$success_msg[] = "Login successful, You will be automatically redirected, if it takes longer than 10 seconds <a href='" . CommonUtils::$WEBSITE_BASE_URL . "'>Click Here</a>." .
						'<script type="text/javascript">setTimeout(function(){window.location="' . CommonUtils::$WEBSITE_BASE_URL . '";}, 5000);</script>';
				}
			}
			else
			{
				$error_msgs[] = $res['error_msg_str'];
			}
		}
		else
		{
			$error_msgs[] = "Username or Password fields can't be empty, please try again.";
		}
	}
}

require_once __DIR__ . "/include/header.footer.class.php";

$Header = new HEADER();
$Header->setTitle("TGDB - Login");
$Header->appendRawHeader(function() { global $Game; ?>

	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.10/css/all.css"
	integrity="sha384-+d0P83n9kaQMCwj8F4RJB66tzIwOKmrdb46+porD/OvrJ+37WqIM7UoBtwHO6Nlg" crossorigin="anonymous">

<?php });?>
<?= $Header->print(); ?>

	<div class="container">
	<?php if(!empty($error_msgs)) : ?>
		<div class="row justify-content-center">
			<div class="alert alert-warning">
				<h4 class="alert-heading">Action Failed!</h4>
				<?php foreach($error_msgs as $msg) : ?>
				<p class="mb-0"><?= $msg ?></p>
				<?php endforeach;?>
			</div>
		</div>
		<?php endif; ?>
		<?php if(!empty($success_msg)) : ?>
		<div class="row justify-content-center">
			<div class="alert alert-success">
				<h4 class="alert-heading">Action Completed!</h4>
				<?php foreach($success_msg as $msg) : ?>
				<p class="mb-0"><?= $msg ?></p>
				<?php endforeach;?>
			</div>
		</div>
		<?php else : ?>
		<div class="row justify-content-center">
			<div class="card">
				<div class="card-header">
					<fieldset>
						<legend>Login</legend>
					</fieldset>
				</div>
				<div class="card-body">
					<form id="login_form" class="form-horizontal" method="post">
					<div class="form-group">
							<div class="input-group mb-3">
								<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-users" aria-hidden="true"> Username </i></span>
								</div>
								<input class="form-control" name="username" id="username" placeholder="Enter your Username" type="text">
							</div>
						</div>

						<div class="form-group">
							<div class="input-group mb-3">
								<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"> Password </i></span>
								</div>
								<input class="form-control" name="password" id="password" placeholder="Enter your Password" type="password">
							</div>
						</div>

						<div class="form-group">
							<div>
								<input type="hidden" name="redirect" value="<?= $_SERVER['HTTP_REFERER'] ?>"/>
								<div><label for="autologin"><input name="autologin" id="autologin" tabindex="4" type="checkbox"> Remember me</label></div>
								<div><label for="viewonline"><input name="viewonline" id="viewonline" tabindex="5" type="checkbox"> Hide my online status this session</label></div>
							</div>
						</div>

						<div class="form-group ">
							<button type="submit" class="btn btn-primary btn-lg btn-block">login</button>
						</div>
						<div class="login-register">
							<a href="https://forums.thegamesdb.net/ucp.php?mode=register">register</a>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>

<?php FOOTER::print(); ?>
