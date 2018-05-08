<?php
require_once __DIR__ . "/../../include/CommonUtils.class.php";
session_start();
require_once __DIR__ . "/login.phpbb.class.php";
$_user = phpBBUser::getInstance();
FOOTER::$_time_start = microtime(true);

class HEADER
{
	private $_title = "TheGamesDB";
	private $_printExtraHeader;

	public function __construct()
	{
		if(!isset($_SESSION['style']))
		{
			$_SESSION['style'] = 5;
		}

		if(isset($_REQUEST['style']))
		{
			$_SESSION['style'] = $_REQUEST['style'];
		}
	}
	public function setTitle($title)
	{
		$this->_title = $title;
	}

	public function appendRawHeader($fun)
	{
		$this->_printExtraHeader = $fun;
	}
	public function print()
	{ global $_user;?>
<!doctype html>
<html lang="en">

<head>
	<title><?= $this->_title ?></title>
	<meta charset="utf-8">
	<!-- allow site to adapt to mobile
		 TODO: add check for mobile desktop toggle -->
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<?php
		// NOTE: this is a placeholder for now, we need to decided if we want to have multi-theme support or choose 1
		switch($_SESSION['style'])
		{
			case 1:
				echo '<link rel="stylesheet" href="/css/darkly-bootstrap.min.css" crossorigin="anonymous">';
				break;
			case 2:
				echo '<link rel="stylesheet" href="/css/litera-bootstrap.min.css" crossorigin="anonymous">';
				break;
			case 3:
				echo '<link rel="stylesheet" href="/css/superhero-bootstrap.min.css" crossorigin="anonymous">';
				break;
			case 4:
				echo '<link rel="stylesheet" href="/css/minty-bootstrap.min.css" crossorigin="anonymous">';
				break;
			case 5:
				echo '<link rel="stylesheet" href="/css/lux-bootstrap.min.css" crossorigin="anonymous">';
				echo "<style>body { color: #424649; } </style>";
				break;
			default:
				echo '<link rel="stylesheet" href="/css/materia-bootstrap.min.css" crossorigin="anonymous">';
		}
	?>
	<script src="https://code.jquery.com/jquery-3.2.1.min.js" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="/css/main.css" crossorigin="anonymous">
	<?php if(isset($this->_printExtraHeader)) : call_user_func($this->_printExtraHeader); endif; ?>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="margin: 10px;">
		<a class="navbar-brand" href="/">TheGamesDB</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarColor01">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item">
					<a class="nav-link" href="/">Home</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="https://forums.thegamesdb.net/">Forums</a>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Browse
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
						<a class="dropdown-item" href="/browse.php">Games</a>
						<a class="dropdown-item" href="/listplatforms.php">Platforms</a>
					</div>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="/stats.php">Stats</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= CommonUtils::$API_BASE_URL ?>">API DOCS</a>
				</li>
			</ul>
			<form action="/search.php" method="get" class="form-inline my-2 my-lg-0">
				<input name="name" class="form-control mr-sm-2" type="text" placeholder="Search">
				<button class="btn btn-secondary my-2 my-sm-0" type="submit">Search</button>
			</form>
			<ul class="navbar-nav my-2 my-lg-0">
				<?php if($_user->isLoggedIn()) : ?>
				<li class="nav-item mr-0 dropdown">
					<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
						style="padding-bottom: 10px;"><img width="25px" src="<?= $_user->GetAvatar() ?>"
						style="border-radius: 50%;"> <?= $_user->GetUsername() ?> <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
						<span class="caret"></span>
					</a>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="https://forums.thegamesdb.net/memberlist.php?mode=viewprofile&u=<?= $_user->GetUserID() ?>">Forum Profile</a>
						<a class="dropdown-item" href="/add_game.php">Add New Game</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="<?= append_sid("/login.php", 'logout', false, $_user->GetUserSessionID()); ?>">Logout</a>
					</div>
				</li>
				<?php else : ?>
				<li class="nav-item mr-0">
					<a class="nav-link" href="/login.php">Log in</a>
				</li>
				<?php endif; ?>
			</ul>
		</div>
	</nav>
	<?php }// endfunction print()
}
?>
<?php
class FOOTER
{
	public static $_time_start = 0;	
	public static function print()
	{ ?>
		<footer class="container-fluid bg-dark" style="margin-top:10px; padding: 20px;">
			<div class="container">
				<div class="row">
					<div class="col-sm-3">
						<h2 class="logo"><a href="/"> TheGamesDB </a></h2>
					</div>
					<div class="col-sm-2">
						<h5>Get started</h5>
						<ul>
							<li><a href="/">Home</a></li>
							<li><a href="#">Sign up</a></li>
							<li><a href="/browse.php">Games</a></li>
							<li><a href="/listplatforms.php">Platforms</a></li>
						</ul>
					</div>
					<div class="col-sm-2">
						<h5>About us</h5>
						<ul>
							<li><a href="#">Company Information</a></li>
							<li><a href="#">Contact us</a></li>
							<li><a href="#">Reviews</a></li>
						</ul>
					</div>
					<div class="col-sm-2">
						<h5>Support</h5>
						<ul>
							<li><a href="#">FAQ</a></li>
							<li><a href="#">Help desk</a></li>
							<li><a href="https://forums.thegamesdb.net/">Forums</a></li>
						</ul>
					</div>
					<div class="col-sm-3">
						<div class="social-networks">
							<a href="https://twitter.com/thegamesdb" class="twitter"><i class="fab fa-twitter"></i></a>
							<a href="https://www.facebook.com/thegamesdb/" class="facebook"><i class="fab fa-facebook"></i></a>
							<a href="https://plus.google.com/116977810662942577082" class="google"><i class="fab fa-google-plus"></i></a>
						</div>
						<button type="button" class="btn btn-info">Contact us</button>
					</div>
				</div>

				<div class="footer-copyright">
					<p>© 2018 TheGamesDB </p>
				</div>
				<p>execution time: <?= microtime(true) - FOOTER::$_time_start ?></p>
			</div>
		</footer>
	</body>
</html>
	<?php }
}

?>
