<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\ErrorPage;
use TheGamesDB\TGDBUtils;
use TheGamesDB\CommonUtils;

global $_user;

if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}

$API = TGDB::getInstance();

if(isset($_REQUEST['id']) && !empty($_REQUEST['id']) && is_numeric($_REQUEST['id']))
{
	$options = array("overview" => true, "players" => true, "rating" => true, "ESRB" => true, "boxart" => true, "coop" => true,
		"genres" => true, "publishers" => true, "platform" => true, "youtube" => true, "alternates" => true, "uids" => true,
		"region_id" => true, "country_id" => true);
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
		if($_user->isLoggedIn())
		{
			$Game->is_booked = $API->isUserGameBookmarked($_user->GetUserID(), $Game->id);
		}
	}
	$Platform = $API->GetPlatforms($Game->platform, array("icon" => true, "overview" => true, "developer" => true));
	if(isset($Platform[$Game->platform]))
	{
		$Platform = $Platform[$Game->platform];
	}
	if($Game->region_id > 0)
	{
		$region = $API->GetGameRegion($Game->region_id);
	}
	if($Game->country_id > 0)
	{
		$country = $API->GetGameCountry($Game->country_id);
	}
}

$GenresList = $API->GetGenres();
$DevsList = $API->GetDevsListByIDs($Game->developers);
$PubsList = $API->GetPubsListByIDs($Game->publishers);

$fanarts = TGDBUtils::GetAllCovers($Game, 'fanart', '');
$screenshots = TGDBUtils::GetAllCovers($Game, 'screenshot', '');
$banners = TGDBUtils::GetAllCovers($Game, 'banner', '');
$clearlogos = TGDBUtils::GetAllCovers($Game, 'clearlogo', '');
$titlescreens = TGDBUtils::GetAllCovers($Game, 'titlescreen', '');
$is_graphics_empty = empty($fanarts) && empty($screenshots) && empty($banners) && empty($clearlogos) && empty($titlescreens);

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

$Header = new Header();
$Header->setTitle("TGDB - Browse - Game - $Game->game_title");
$Header->appendRawHeader(function() { global $Game, $box_cover, $_user; ?>

	<meta property="og:title" content="<?= $Game->game_title; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="<?= !empty($box_cover->front) ? $box_cover->front->thumbnail : "" ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($Game->overview); ?>" />

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
			fancyboxOpts.share.descr = function(instance, item)
			{
				return "<?= $Game->game_title ?>";
			};
			$('[data-fancybox]').fancybox(fancyboxOpts);

			$('#reportbtn').click(function()
			{
				<?php if ($_user->isLoggedIn()) : ?>
				var game_id = parseInt(prompt("Please enter the original game id", ""));
				if(isNaN(game_id))
				{
					alert('Invalid game id.')
					return;
				}
				$(this).append('<i class="fa fa-spinner fa-pulse"></i>');
				$(this).attr("disabled", true);
				$.ajax({
					method: "POST",
					url: "/actions/report_game.php",
					data: {
						game_id: <?= $Game->id ?>,
						report_type:1,
						metadata_0: game_id,
					 }
				})
				.done(function( msg ) {
					$('#reportbtn').attr("disabled", false);
					$('#reportbtn').find('.fa').remove();
					var response = JSON.parse(msg);
					alert(msg);
				});
				<?php else : ?>
				alert("You must login to use this feature.");
				<?php endif; ?>
			});

			$('[data-toggle="bookmark"]').click(function()
			{
				<?php if ($_user->isLoggedIn()) : ?>
				$(this).append('<i class="fa fa-spinner fa-pulse"></i>');
				$(this).attr("disabled", true);
				$.ajax({
					method: "POST",
					url: "/actions/add_game_bookmark.php",
					data: {
						games_id: <?= $Game->id ?>,
						is_booked: $('[data-toggle="bookmark"]').data("is-booked"),
					 }
				})
					.done(function( msg ) {
					$('[data-toggle="bookmark"]').attr("disabled", false);
					$('[data-toggle="bookmark"]').find('.fa').remove();
					var response = JSON.parse(msg);
					if (response['code'] == 0 )
					{
						if (response['msg'] == 1)
						{
							$('[data-toggle="bookmark"]')[0].innerHTML='Remove From Collection';
							$('[data-toggle="bookmark"]').removeClass( "btn-secondary" ).addClass( "btn-danger" );
							$('[data-toggle="bookmark"]').data("is-booked", 0);
						}
						else
						{
							$('[data-toggle="bookmark"]')[0].innerHTML='Add To Collection';
							$('[data-toggle="bookmark"]').removeClass( "btn-danger" ).addClass( "btn-secondary" );
							$('[data-toggle="bookmark"]').data("is-booked", 1);
						}
					} else {
						alert("Bookmark failed, refresh page and try again.");
					}
				});
				<?php else : ?>
				alert("You must login to use this feature.");
				<?php endif; ?>
			});
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

		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
				<?php if(!empty($cover = $fanarts) || !empty($cover = $screenshots)): ?>
					<img alt="cover" class="cover cover-offset" src="<?= $cover[0]->medium ?>"/>
				<?php else: ?>
					<img alt="cover" class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
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
								<img alt="front cover" class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php if(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" style="display:none;" data-fancybox="cover" data-caption="Back Cover"
								href="<?= $box_cover->back->original ?>" data-thumb="<?= $box_cover->back->thumbnail ?>"/>
							</a>
							<?php endif; ?>
								
							<?php elseif(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" data-fancybox="cover" data-caption="Back Cover" href="<?= $box_cover->front->original ?>">
								<img alt="back cover" class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php else: ?>
							<img alt="cover placeholder" class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage($Game->game_title, 'boxart'); ?>"/>
							<?php endif; ?>
							<div class="card-body">
							<?php if(isset($Game->is_booked) && $Game->is_booked == 1) : ?>
								<button type="button" data-is-booked="0" data-toggle="bookmark" class="btn btn-danger btn-block btn-wrap-text">Remove From Collection</button>
							<?php else: ?>
								<button type="button" data-is-booked="1" data-toggle="bookmark" class="btn btn-secondary btn-block btn-wrap-text">Add To Collection</button>
							<?php endif;?>
							</div>
							<div class="card-body">
								<?php if (!empty($Platform)) : ?>
								<p>Platform: <a href="/platform.php?id=<?= $Platform->id?>"><?= $Platform->name; ?></a></p>
								<?php endif; ?>
								<p>Region: <?= isset($region) ? $region->name : "Region Not Set" ?></p>
								<?php if(isset($country)) : ?>
								<p>Country: <?= $country->name; ?></a></p>
								<?php endif; ?>
								<?php if (!empty($Game->developers) && !empty($DevsList)) : ?>
								<p>Developer(s): <?php $last_key = end(array_keys($DevsList)); foreach($DevsList as $key => $Dev) : ?>
								<a href="list_games.php?dev_id=<?= $Dev->id ?>"><?= $Dev->name ?></a><?= ($key != $last_key) ? " | " : "" ?>
								<?php endforeach; ?></p>
								<?php endif;  if (!empty($Game->publishers) && !empty($PubsList)) : ?>
								<p>Publishers(s): <?php $last_key = end(array_keys($PubsList)); foreach($PubsList as $key => $pub) : ?>
								<a href="list_games.php?pub_id=<?= $pub->id ?>"><?= $pub->name ?></a><?= ($key != $last_key) ? " | " : "" ?>
								<?php endforeach; ?></p>
								<?php endif; if (!empty($Game->release_date)) : ?>
								<p>ReleaseDate: <?= $Game->release_date ;?></p>
								<?php endif; if (!empty($Game->PlatformDetails)) : ?>
								<p>Platform: <?= $Game->PlatformDetails->name; ?></p>
								<?php endif; if (!empty($Game->players)) : ?>
								<p>Players: <?= $Game->players; ?></p>
								<?php endif; if (!empty($Game->coop)) : ?>
								<p>Co-op: <?= $Game->coop; ?></p>
								<?php endif; ?>
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
								<?php if(!empty($Game->alternates)) : ?><h6 class="text-muted">Also know as: <?= implode(" | ", $Game->alternates) ?></h6><?php endif; ?>
							</div>
							<div class="card-body">
								<p class="game-overview"><?= !empty($Game->overview) ? $Game->overview : "No overview is currently available for this title, please feel free to add one.";?></p>
								<?php if (!empty($Game->youtube)) : ?>
								<p>Trailer: <a data-fancybox data-caption="Trailer" href="https://youtube.com/watch?v=<?= $Game->youtube?>">YouTube</a></p>
								<?php endif; if (!empty($Game->rating)) : ?>
								<p>ESRB Rating: <?= $Game->rating; ?></p>
								<?php endif; if (!empty($Game->genres)) : ?>
								<?php
								$genres = [];
								foreach($Game->genres as $gen_id)
									{
										$genres[] = $GenresList[$gen_id]->name;
									}
								?>
								<p>Genre(s): <?= implode(" | ", $genres) ?></p>
								<?php endif; if (!empty($Game->uids)) : ?>
								<?php
								$uids = [];
									foreach($Game->uids as $item)
									{
										$uids[] = $item->uid;
									}
								?>
								<p>UID(s): <?= implode(" | ", $uids) ?></p>
								<?php endif; ?>
							</div>
							<div class="card-footer" style="text-align: center;">
								<p>Share Via</p>
								<!-- Twitter -->
								<div data-url="https://twitter.com/intent/tweet?text=<?= urlencode("Checkout '$Game->game_title' on ")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" title="Share on Twitter" class="btn btn-twitter">
									<i class="fab fa-twitter"></i>
								</div>
								<!-- Facebook -->
								<div data-url="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" title="Share on Facebook" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" class="btn btn-facebook">
									<i class="fab fa-facebook"></i>
								</div>
								<!-- StumbleUpon -->
								<div data-url="http://www.stumbleupon.com/submit?url=<?= urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>" title="Share on StumbleUpon" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" data-placement="top" class="btn btn-stumbleupon">
									<i class="fab fa-stumbleupon"></i>
								</div>
								<!-- Pinterest -->
								<div data-url="https://www.pinterest.com/pin/create/button/?description=<?= urlencode("Checkout '$Game->game_title' on " . CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id")."&amp;url=".urlencode(CommonUtils::$WEBSITE_BASE_URL . "game.php?id=$Game->id");?>&media=<?= !empty($box_cover->front) ? urlencode($box_cover->front->thumbnail) : "" ?>" title="Share on Pinterest" onclick="javascript:window.open($(this).data('url'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;" data-placement="top" class="btn btn-pinterest">
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
											<img alt="fanart(s)" class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>" alt=""/>
											<img alt="fanart ribbon" src="/images/ribbonFanarts.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($fanarts)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="fanarts" data-caption="Fanart"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($titlescreens))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="titlescreens" data-caption="Title Screen" href="<?= $cover->original ?>">
											<img alt="titlescreen(s)" class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img alt="titlescreen ribbon" src="/images/ribbonTitlescreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($titlescreens)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="titlescreens" data-caption="Title Screen"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($screenshots))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="screenshots" data-caption="Screenshot" href="<?= $cover->original ?>">
											<img alt="screenshot(s)" class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img alt="screenshot ribbon" src="/images/ribbonScreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($screenshots)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="screenshots" data-caption="Screenshot"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($banners))) : ?>
									<div class="col-8" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="banners" data-caption="Banner" href="<?= $cover->original ?>">
											<img alt="banner(s)" class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img alt="banner ribbon" src="/images/ribbonBanners.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($banners)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="banners" data-caption="Banner"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($clearlogos))) : ?>
								</div>
								<div class="row justify-content-center">

									<div class="col-5" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="clearlogos" data-caption="Clearlogo" href="<?= $cover->original ?>">
											<img alt="clearlogo(s)" style ="background-color: black;"class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img alt="clearlogo ribbon" src="/images/ribbonClearlogos.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($clearlogos)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="clearlogos" data-caption="Clearlogo"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if($is_graphics_empty) : ?>
								</div>
								<div class="row justify-content-center">
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
							<p><button id="reportbtn" class="btn btn-primary btn-block">Report Duplicate</button></p>
							<!--<p><a href="https://forums.thegamesdb.net/memberlist.php?mode=contactadmin&subject=<?= urlencode("[REPORT][GAME:$Game->id][$Game->game_title]") ?>" class="btn btn-primary btn-block">Report</a></p>-->
							<p><a href="/edit_game.php?id=<?= $Game->id ?>" class="btn btn-primary btn-block">Edit</a></p>
							<?php if($_user->isLoggedIn() && $_user->hasPermission('m_delete_games')) : ?>
							<p><a href="/contr.php?id=<?= $Game->id ?>" class="btn btn-primary btn-block">View Edits</a></p>
							<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</div>

<?php Footer::print(); ?>
