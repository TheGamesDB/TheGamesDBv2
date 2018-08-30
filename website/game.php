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

if(isset($_REQUEST['id']) && !empty($_REQUEST['id']) && is_numeric($_REQUEST['id']))
{
	$options = array("overview" => true, "players" => true, "rating" => true, "ESRB" => true, "boxart" => true, "coop" => true,
		"genre" => true, "publisher" => true, "platform" => true, "youtube" => true);
	$API = TGDB::getInstance();
	$list = $API->GetGameByID($_REQUEST['id'], 0, 1, $options);
	if(empty($list))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_REMOVED_GAME_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
	else
	{
		$Game = array_shift($list);
		$covers = $API->GetGameBoxartByID($_REQUEST['id'], 0, 9999, 'ALL');
		if(!empty($covers))
		{
			$Game->boxart = $covers[$_REQUEST['id']];
		}
	}
	$Platform = $API->GetPlatforms($Game->platform, array("icon" => true, "overview" => true, "developer" => true));
	if(isset($Platform[$Game->platform]))
	{
		$Platform = $Platform[$Game->platform];
	}
}


$fanarts = TGDBUtils::GetAllCovers($Game, 'fanart', '');
$screenshots = TGDBUtils::GetAllCovers($Game, 'screenshot', '');
$banners = TGDBUtils::GetAllCovers($Game, 'series', '');
$is_graphics_empty = empty($fanarts) && empty($screenshots) && empty($banners);

$box_cover =  new \stdClass();
$box_cover->front = TGDBUtils::GetAllCovers($Game, 'boxart', 'front');
if(!empty($box_cover->front))
{
	$box_cover->front = $box_cover->front[0];
}
$box_cover->back = TGDBUtils::GetAllCovers($Game, 'boxart', 'back');
if(!empty($box_cover->back))
{
	$box_cover->back = $box_cover->back[0];
}

$Header = new HEADER();
$Header->setTitle("TGDB - Browse - Game - $Game->game_title");
$Header->appendRawHeader(function() { global $Game; ?>

	<meta property="og:title" content="<?= $Game->game_title; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="<?= !empty($box_cover->front) ? $box_cover->front->thumbnail : "" ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($Game->overview); ?>" />

	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>

	<script type="text/javascript" src="/js/jquery.fancybox.3.3.5.js"></script>
	<script type="text/javascript" src="/js/fancybox.config.js"></script>

	<script type="text/javascript">
		$(document).ready(function()
		{
			fancyboxOpts.share.descr = function(instance, item)
			{
				return "<?= $Game->game_title ?>";
			};
			$('[data-fancybox]').fancybox(fancyboxOpts);
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
	</style>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">

		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
				<?php if(!empty($cover = $fanarts) || !empty($cover = $screenshots)): ?>
					<img class="cover cover-offset" src="<?= $cover[0]->medium ?>"/>
				<?php else: ?>
					<img class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
				<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="row">

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<?php if(!empty($box_cover->front)) : ?>
							<a class="fancybox-thumb" data-fancybox="cover" data-caption="Front Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php if(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" style="display:none;" data-fancybox="cover" data-caption="Back Cover"
								href="<?= $box_cover->back->original ?>" data-thumb="<?= $box_cover->back->thumbnail ?>"/>
							</a>
							<?php endif; ?>
								
							<?php elseif(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" data-fancybox="cover" data-caption="Back Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php else: ?>
							<img class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage($Game->game_title, 'boxart'); ?>"/>
							<?php endif; ?>
							<div class="card-body">
							<?php if(false) : ?>
								<button type="button" data-toggle="bookmark" class="btn btn-danger btn-block btn-wrap-text">Remove From Collection <span class="glyphicon glyphicon-ok"></span></button>
							<?php else: ?>
								<button type="button" data-toggle="bookmark" class="btn btn-secondary btn-block btn-wrap-text">Add To Collection</button>
							<?php endif;?>
							</div>
							<div class="card-body">
								<?php if (!empty($Platform)) : ?>
								<p>Platform: <a href="/platform.php?id=<?= $Platform->id?>"><?= $Platform->name; ?></a></p>
								<?php endif; if (!empty($developer)) : ?>
								<p>Developer: <?= $Game->developer; ?></p>
								<?php endif; if (!empty($Game->publisher)) : ?>
								<p>Publisher: <?= $Game->publisher; ?></p>
								<?php endif; if (!empty($Game->release_date)) : ?>
								<p>release_date: <?= $Game->release_date ;?></p>
								<?php endif; if (!empty($Game->PlatformDetails)) : ?>
								<p>Platform: <?= $Game->PlatformDetails->name; ?></p>
								<?php endif; if (!empty($Game->players)) : ?>
								<p>Players: <?= $Game->players; ?></p>
								<?php endif; if (!empty($Game->coop)) : ?>
								<p>Co-op: <?= $Game->coop; ?></p>
								<?php endif ?>
							</div>
						</div>
					</div>
				</div>
				<?php if (isset($login) && $login->isUserLoggedIn() && $_SESSION['user_access_level'] == 255) : ?>
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								Extras
							</div>
							<div class="card-body">
								<?php if (isset($Game->id)) : ?>
								TGDB ID: <?= $Game->id; ?><br/>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
				<?php endif;?>
			</div>

			<div class="col-12 col-md-9 col-lg-8">
				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1><?= $Game->game_title;?></h1>
							</div>
							<div class="card-body">
								<p><?= !empty($Game->overview) ? $Game->overview : "No overview is currently available for this title, please feel free to add one.";?></p>
								<?php if (!empty($Game->youtube)) : ?>
								<p>Trailer: <a data-fancybox data-caption="Trailer" href="https://youtube.com/watch?v=<?= $Game->youtube?>">YouTube</a></p>
								<?php endif;?>
							</div>
							<div class="card-footer" style="text-align: center;">
								<p>Share Via</p>
								<!-- Twitter -->
								<div data="https://twitter.com/intent/tweet?text=<?= urlencode("Checkout '$Game->game_title' on ")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" onclick="javascript:window.open(this.attributes['data'].value, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" title="Share on Twitter" target="_blank" class="btn btn-twitter">
									<i class="fab fa-twitter"></i>
								</div>
								<!-- Facebook -->
								<div data="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" title="Share on Facebook" onclick="javascript:window.open(this.attributes['data'].value, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" target="_blank" class="btn btn-facebook">
									<i class="fab fa-facebook"></i>
								</div>
								<!-- Google+ -->
								<div data="https://plus.google.com/share?url=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" title="Share on Google+" onclick="javascript:window.open(this.attributes['data'].value, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=600');return false;" target="_blank" class="btn btn-googleplus">
									<i class="fab fa-google-plus"></i>
								</div>
								<!-- StumbleUpon -->
								<div data="http://www.stumbleupon.com/submit?url=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" title="Share on StumbleUpon" onclick="javascript:window.open(this.attributes['data'].value, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" target="_blank" data-placement="top" class="btn btn-stumbleupon">
									<i class="fab fa-stumbleupon"></i>
								</div>
								<!-- Pinterest -->
								<div data="https://www.pinterest.com/pin/create/button/?description=<?= urlencode("Checkout '$Game->game_title' on " . CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>&media=<?= !empty($box_cover->front) ? urlencode($box_cover->front->thumbnail) : "" ?>" title="Share on Pinterest" onclick="javascript:window.open(this.attributes['data'].value, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" target="_blank" data-placement="top" class="btn btn-pinterest">
									<i class="fab fa-pinterest"></i>
								</div>
							</div>
						</div>
					</div>
				</div>

				<?php if (true) : ?>
				<div class="row" style="padding-bottom:10px;">
					<div class="col">
						<div class="card border-primary">
							<h3  class="card-header">
								Other Graphic(s)
							</h3>
							<div class="card-body">
								<div class="row justify-content-center">
									<?php if(!empty($cover = array_shift($fanarts))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="fanarts" data-caption="Fanart" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>" alt=""/>
											<img src="/images/ribbonFanarts.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($fanarts)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="fanarts" data-caption="Fanart"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($screenshots))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="screenshots" data-caption="Screenshot" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img src="/images/ribbonScreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($screenshots)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="screenshots" data-caption="Screenshot"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($banners))) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="banners" data-caption="Banner" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img src="/images/ribbonBanners.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($banners)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="banners" data-caption="Banner"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if($is_graphics_empty) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<h5>No fanarts/screenshots/banners found, be the 1st to add them.</h5>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>

			<?php if($_user->isLoggedIn() && $_user->hasPermission('u_edit_games')) : ?>
			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<legend>Control Panel</legend>
							</div>
							<div class="card-body">
							<p><a href="https://forums.thegamesdb.net/memberlist.php?mode=contactadmin&subject=<?= urlencode("[REPORT][GAME:$Game->id][$Game->game_title]") ?>" class="btn btn-primary btn-block">Report</a></p>
							<p><a href="/edit_game.php?id=<?= $Game->id ?>" class="btn btn-primary btn-block">Edit</a></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</div>

<?php FOOTER::print(); ?>
