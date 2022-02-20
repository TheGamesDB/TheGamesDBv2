<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";
require_once __DIR__ . "/include/login.common.class.php";

$_user = phpBBuser::getInstance();

{
	$game_id = $_REQUEST['id'];
	$API = TGDB::getInstance();
	$list = $API->GetGameEditContributors($_REQUEST['id']);
	if($_REQUEST['id'] < 60000)
	{
		$legacy = $API->GetLegacyCopy($_REQUEST['id']);
	}
	$Game = array_shift($API->GetGameByID($_REQUEST['id'], 0, 1));
}


$Header = new HEADER();
$Header->setTitle("TGDB - Browse - GameEdits - $Game->game_title");
$Header->appendRawHeader(function() { global $Game, $_user; ?>

	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>

	<script src="/js/jquery.fancybox.3.3.5.js"></script>
	<script src="/js/fancybox.config.js"></script>

	<script>
		$(document).ready(function()
		{
			
		});
	</script>
	<style>
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
	</style>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">

		<div class="row">
			<div class="col">
				<?php if(isset($list) && !empty($list)): ?>
				<table class="table">
					<thead class="thead-dark">
						<tr>
							<th scope="col">Edit ID</th>
							<th scope="col">Username</th>
							<th scope="col">Timestamp</th>
							<th scope="col">Type</th>
							<th scope="col">Value</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($list as $contrib): ?>
						<tr>
							<th scope="row"><?= $contrib->id; ?></th>
							<td><a href="/user_contrib.php?id=<?= $contrib->users_id ?>"><?= $contrib->username; ?></a></td>
							<td><?= $contrib->timestamp; ?></td>
							<td><?= $contrib->type; ?></td>
							<td><?= $contrib->value; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php else: ?>
				<h2>No user edits on record. You can check legacy edit below.</h2>
				<?php endif; ?>
			</div>
		</div>

		<?php if(isset($legacy) && !empty($legacy)): ?>
		<div class="row">
			<div class="col">
				<h2>legacy data (reference only)</h2>
				<table class="table">
					<thead class="thead-dark">
						<tr>
							<th scope="col">Type</th>
							<th scope="col">Value</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($legacy as $key => $value): ?>
						<tr>
							<td><?= $key; ?></td>
							<td><?= $value; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif ;?>
	</div>


<?php FOOTER::print(); ?>
