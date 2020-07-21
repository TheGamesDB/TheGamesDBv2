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
require_once __DIR__ . "/include/login.common.class.php";
require_once __DIR__ . "/include/PaginationUtils.class.php";

$API = TGDB::getInstance();
$page = PaginationUtils::getPage();
$limit = 18;
$offset = ($page - 1) * $limit;
$list = $API->GetUserEditsByUserID($_REQUEST['id'], $offset, $limit + 1);
$count = $API->GetUserEditsCountByUserID($_REQUEST['id']);

$displayMin = ($page - 1) * $limit;
$displayMax = $page * $limit;
if($displayMax > $count)
{
	$displayMax = $count;
}
if($has_next_page = count($list) > $limit)
{
	unset($list[$limit]);
}

foreach($list as $Game)
{
	$IDs[] = $Game->id;
}
if(isset($IDs) && !empty($IDs))
{
	$covers = $API->GetGameBoxartByID($IDs, 0, 9999);
	foreach($list as $Game)
	{
		if(isset($covers[$Game->id]))
		{
			$Game->boxart = $covers[$Game->id];
		}
	}
}
//print_r($list);

$Header = new HEADER();
$Header->setTitle("TGDB - Browse - Game - $Game->game_title");
$Header->appendRawHeader(function() { global $Game, $box_cover, $_user; ?>

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
			<?php if(isset($list) && !empty($list)): ?>
			<div class="col-12"><h2><?= $displayMin ?> - <?= $displayMax ?> / <?= $count ?></h2></div>
			<?php foreach($list as $Game) : ?>
				<div class="col-6 col-md-2">
					<div style="padding-bottom:12px; height: 100%">
						<a href="./game.php?id=<?= $Game->id ?>">
							<div class="card border-primary" style="height: 100%">
								<img class="card-img-top" alt="<?= $Game->game_title ?>" src="<?= TGDBUtils::GetCover($Game, 'boxart', 'front', true, true, 'thumb') ?>">
								<div class="card-body card-noboday" style="text-align:center;">
								</div>
								<div class="card-footer bg-secondary" style="text-align:center;">
									<p><?= $Game->game_title ?></p>
									<p><?= $Game->release_date ?></p>
								</div>
							</div>
						</a>
					</div>
				</div>
			<?php endforeach; else : ?>
				<div class="col-12 col-md-10">
					<div class="card">
						<div class="card-body">
							<h3>This User has no contributions.</h3>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?= (isset($page)) ? PaginationUtils::Create($has_next_page) : "";?>
	</div>


<?php FOOTER::print(); ?>
