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
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";

$API = TGDB::getInstance();
$_user = phpBBuser::getInstance();

$fields = array("id" => true, "name" => true, "alias" => true, "icon" => true, "console" => true, "controller" => true, "developer" => true, "manufacturer" => true, "media" => true, "cpu" => true, "memory" => true, "graphics" => true, "sound" => true, "maxcontrollers" => true, "display" => true, "overview" => true, "youtube" => true);
$Platform = $API->GetPlatforms($_REQUEST['id'], $fields);
if(isset($Platform[$_REQUEST['id']]))
{
	$Platform = $Platform[$_REQUEST['id']];
}
else
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
	$errorPage->print_die();
}
$covers = $API->GetPlatformBoxartByID($_REQUEST['id'], 0, 1, 'boxart');
$fanart = $API->GetPlatformBoxartByID($_REQUEST['id'], 0, 1, 'fanart');
if(isset($covers) && !empty($covers))
{
	$Platform->boxart[] = $covers[$_REQUEST['id']][0];
}
if(isset($fanart) && !empty($fanart))
{
	$Platform->boxart[] = $fanart[$_REQUEST['id']][0];
	$fanart = true;
}
$recent = $API->GetGamesByDateByPlatform($_REQUEST['id'], date("d/m/Y"), 0, 6, array('BEFORE' => true), "release_date", 'DESC');
foreach($recent as $Game)
{
	$IDs[] = $Game->id;
}
if(isset($IDs) && !empty($IDs))
{
	$covers = $API->GetGameBoxartByID($IDs, 0, 9999, 'boxart');
	foreach($recent as $Game)
	{
		if(isset($covers[$Game->id]))
		{
			$Game->boxart = $covers[$Game->id];
		}
	}
}

$Header = new HEADER();
$Header->setTitle("TGDB - Browse - Platforms");
$Header->appendRawHeader(function()
{
	global $Platform; ?>

	<meta property="og:title" content="<?= $Platform->name; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="<?= TGDBUtils::GetCover($Platform, 'boxart', '', true,  true, 'thumb') ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($Platform->overview); ?>" />

	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script src="/js/jquery.fancybox.3.3.5.js"></script>
	<script src="/js/fancybox.config.js"></script>

	<script>
		$(document).ready(function()
		{
			fancyboxOpts.share.descr = function(instance, item)
			{
				return "<?= $Platform->name ?>";
			};
			$('[data-fancybox]').fancybox(fancyboxOpts);
		});
	</script>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">

		<?php if(!empty($fanart)): ?>
		<div class="row" style="padding-bottom:10px;">
			<div class="col">
				<div id="cover" class="view-width" style="max-width: 100%; height: 300px; overflow: hidden; text-align: center;padding: 0px;;">
					<img alt='CoverIMG' class="cover" style=" margin-top: -170px;" src="<?= TGDBUtils::GetCover($Platform, 'fanart', '', true,  false, 'medium') ?>" />
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div class="row">

			<div class="col-xs-12 col-sm-4 col-md-2" style="text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<img class="card-img-top" alt='PosterIMG' src="<?= TGDBUtils::GetCover($Platform, 'boxart', '', true,  true, 'thumb') ?>" />
							<div class="card-body">
							<button onclick="alert('Not Implemented Yet!')" type="button" data-toggle="bookmark" class="btn btn-secondary btn-block btn-wrap-text">Add To Collection</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-xs-12 col-sm-8 col-md-8">
				<div class="row" style="text-align: center; padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1><?= $Platform->name;?></h1>
							</div>
							<div class="card-body">
								<p>
									<?= $Platform->overview;?></p>
							</div>
							<div class="card-body">
								<?php if(!empty($Platform->manufacturer)) : ?>
								<p>Manufacturer: <?= $Platform->manufacturer; ?></p>
								<?php endif; if(!empty($Platform->developer)) : ?>
								<p>Developer: <?= $Platform->developer; ?></p>
								<?php endif; if(!empty($Platform->media)) : ?>
								<p>Media Medium: <?= $Platform->media ;?></p>
								<?php endif; if(!empty($Platform->cpu)) : ?>
								<p>CPU: <?= $Platform->cpu ;?></p>
								<?php endif; if(!empty($Platform->memory)) : ?>
								<p>Memory: <?= $Platform->memory ;?></p>
								<?php endif; if(!empty($Platform->graphics)) : ?>
								<p>Graphics: <?= $Platform->graphics ;?></p>
								<?php endif; if(!empty($Platform->sound)) : ?>
								<p>Sound: <?= $Platform->sound ;?></p>
								<?php endif; if(!empty($Platform->display)) : ?>
								<p>Display: <?= $Platform->display ;?></p>
								<?php endif; if(!empty($Platform->maxcontrollers)) : ?>
								<p>Max Controllers: <?= $Platform->maxcontrollers ;?></p>
								<?php endif; if(!empty($Platform->youtube)) : ?>
								<p><a data-fancybox data-caption="Trailer" href="http://youtube.com/watch?v=<?= $Platform->youtube;?>">Watch Trailer</a>
								</p>
								<?php endif; ?>
							</div>
							<div class="card-footer" style="text-align: center;">
								<!-- chaning div to a, and data to href causes these to disappear on OSX, perhaps due to add blocker -->
								<p>Share Via</p>
								<div data-url="https://twitter.com/intent/tweet?text=<?= urlencode(" Checkout '$Platform->name' on ")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL ."platform.php?id=$Platform->id");?>" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" title="Share on Twitter" class="btn btn-twitter">
									<i class="fab fa-twitter"></i>
								</div>
								<div data-url="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "platform.php?id=$Platform->id");?>" title="Share on Facebook" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" class="btn btn-facebook">
									<i class="fab fa-facebook"></i>
								</div>
								<div data-url="http://www.stumbleupon.com/submit?url=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "platform.php?id=$Platform->id");?>" title="Share on StumbleUpon" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" data-placement="top" class="btn btn-stumbleupon">
									<i class="fab fa-stumbleupon"></i>
								</div>
								<div data-url="https://www.pinterest.com/pin/create/button/?description=<?= urlencode(" Checkout '$Platform->name' on " . CommonUtils::$WEBSITE_BASE_URL ."platform.php?id=$Platform->id")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL . "platform.php?id=$Platform->id"); ?>&media=
									<?= urlencode(TGDBUtils::GetCover($Platform, 'boxart', '', false, true, 'thumb')) ?>" title="Share on Pinterest" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" data-placement="top" class="btn btn-pinterest">
									<i class="fab fa-pinterest"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php if(!empty($recent)) : ?>
				<div class="row" style="text-align: center; padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<h3 class="card-header">
							Latest Releases
							</h3>
							<div class="card-body">
								<div class="row">
									<?php foreach($recent as $Game) : ?>
									<div class="col-6 col-md-2">
										<div style="padding-bottom:12px; height: 100%">
											<a href="./game.php?id=<?= $Game->id ?>">
												<div class="card border-primary" style="height: 100%">
													<img class="card-img-top" alt="<?= $Game->game_title ?>" src="<?= TGDBUtils::GetCover($Game, 'boxart', 'front', true, true, 'thumb') ?>">
													<div class="card-body card-noboday" style="text-align:center;">
													</div>
													<div class="card-footer bg-secondary" style="text-align:center;">
														<p>
															<?= WebUtils::truncate($Game->game_title, 20, true) ?></p>
													</div>
												</div>
											</a>
										</div>
									</div>
									<?php endforeach; ?>
									<div class="col-md-12">
										<a href="/list_games.php?platform_id=<?= $Platform->id ?>" class="btn btn-info" role="button" style="width:100%;">See More</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<?php if($_user->isLoggedIn() && $_user->hasPermission('m_delete_games')) : ?>
			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<legend>Control Panel</legend>
							</div>
							<div class="card-body">
							<p><a href="/edit_platform.php?id=<?= $Platform->id ?>" class="btn btn-primary btn-block">Edit</a></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

		</div>

	</div>

<?php FOOTER::print(); ?>
