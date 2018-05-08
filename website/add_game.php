<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
require_once __DIR__ . "/include/login.phpbb.class.php";
$_user = phpBBUser::getInstance();
if(!$_user->isLoggedIn())
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
	$errorPage->print_die();
}
else
{
	if(!$_user->hasPermission('u_edit_games'))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
		$errorPage->print_die();
	}
}

require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";


$API = TGDB::getInstance();
$PlatformList = $API->GetPlatformsList();

$Header = new HEADER();
$Header->setTitle("TGDB - Add Game");
$Header->appendRawHeader(function() { ?>

	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>


	<script type="text/javascript">
		function isJSON(json)
		{
			try
			{
				return (JSON.parse(json) && !!json);
			}
			catch (e)
			{
				return false;
			}
		}

		$(document).ready(function()
		{
			$("#game_add").submit(function(e)
			{

				var url = "./actions/add_game.php";

				$.ajax({
					type: "POST",
					url: url,
					data: $("#game_add").serialize(), 
					success: function(data)
					{
						if(isJSON(data))
						{
							var obj = JSON.parse(data);
							if(obj.code == 1)
							{
								alert(data)
								window.location.href="<?= CommonUtils::$WEBSITE_BASE_URL ?>/game.php?id=" + obj.msg;
								return;
							}
							alert(data)
							return;
						}
						else
						{
							alert("Error: Something Unexpected Happened.")
							return;
						}
					}
				});

				e.preventDefault();
			});
		});
	</script>
	<style type="text/css">
		.cover
		{
			width: 100%;
			position: relative;
		}
		
		@media screen and (min-width: 800px)
		{
			.cover-offset
			{
				margin-top: <?= isset($_REQUEST['test']) ? "-250px" : "-170px" ?>;
			}
			.fanart-banner
			{
				max-height: 100%;
				height: <?= isset($_REQUEST['test']) ? "200px" : "325px" ?>;
				overflow: hidden;
				text-align: center;
			}
		}

		@media screen and (max-width: 800px)
		{
			.cover-offset
			{
				margin-top: 0;
			}

			.fanart-banner
			{
				max-height: 100%;
				height: 175px;
				overflow: hidden;
				text-align: center;
			}
		}
		.margin5px
		{
			margin: 5px;
		}
	</style>

	
<?php });?>
<?= $Header->print(); ?>

	<form id="game_add" class="container-fluid">
		<input name="game_id" type="hidden" value="<?= $Game->id ?>"/>
		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
					<img class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
				</div>
			</div>
		</div>

		<div class="row">

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<img class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage("Placeholder", 'boxart'); ?>"/>
							<div class="card-body">
								<p>Platform: <select name="Platform" style="width:100%">
									<?php foreach($PlatformList as $Platform) : ?>
										<option value="<?= $Platform->id ?>"><?= $Platform->name ?></option>
									<?php endforeach; ?>
									</select>
								</p>
								<p>Developer: <input type="text" name="Developer" placeholder="Developer..."/></p>
								<p>Publisher: <input type="text" name="Publisher" placeholder="Publisher..."/></p>
								<p>ReleaseDate*:<br/> <input id="date" name="ReleaseDateRevised" type="date" value="1970-01-01"></p>
								<p>Players:
									<select name="Players">
									<?php for($x = 0; $x < 17; ++$x) : ?>
										<option value="<?= $x ?>" <?= (1 == $x) ? "selected" : "" ?>><?= $x ?></option>
									<?php endfor; ?>
									</select>
								</p>
								<p>Co-op:
									<select name="coop">
										<option value="Yes">Yes</option>
										<option value="No" selected>No</option>
									</select>
								</p>
								<p>* : safari doesnt support calender input yet, so please keep date format to (yyyy-mm-dd)</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-12 col-md-9 col-lg-8">
				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1><input style="width:100%" name="GameTitle" placeholder="GameTitle goes here..."/></h1>
							</div>
							<div class="card-body">
								<p>
									<textarea name="Overview" rows=10 style="width:100%" placeholder="No overview is currently available for this title, please feel free to add one."></textarea>
								</p>
								<p>YouTube Trailer: <input name="Youtube"/></p>
							</div>
						</div>
					</div>
				</div>

				<?php if (true) : ?>
				<div class="row" style="padding-bottom:10px;">
					<div class="col">
						<div class="card border-primary">
							<h3 class="card-header">
								Other Graphic(s)
							</h3>
							<div class="card-body">
								<div class="row justify-content-center">
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<h5>You can add fanarts/screenshots/banners found, after the game is added.</h5>
									</div>
								</div>
							</div>
							
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<legend>Control Panel</legend>
							</div>
							<div class="card-body">
								<p><button type="submit" class="btn btn-success btn-block">Add</button></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</form>

<?php FOOTER::print(); ?>
