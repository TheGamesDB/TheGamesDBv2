<?php
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/include/PaginationUtils.class.php";

$API = TGDB::getInstance();
$soon = $API->GetGamesByDate(date("d/m/Y"), 0, 5, array('AFTER' => true), "release_date", 'ASC');
$recent = $API->GetGamesByDate(date("d/m/Y"), 0, 6, array('BEFORE' => true), "release_date", 'DESC');
foreach($soon as $Game)
{
	$PlatformIDs[] = $Game->platform;
}
foreach($recent as $Game)
{
	$IDs[] = $Game->id;
	$PlatformIDs[] = $Game->platform;
}

$API = TGDB::getInstance();
$limit = 18;
$page = 1;
$offset = ($page - 1) * $limit;
$lastupdated = $API->GetAllGames($offset, $limit + 1, array('overview' => true), "id", 'DESC');
if($has_next_page = count($lastupdated) > $limit)
{
	unset($lastupdated[$limit]);
}

foreach($lastupdated as $Game)
{
	$IDs[] = $Game->id;
	$PlatformIDs[] = $Game->platform;
}
$Platforms = $API->GetPlatforms($PlatformIDs);
$covers = $API->GetGameBoxartByID($IDs, 0, 9999);
foreach($recent as $Game)
{
	if(isset($covers[$Game->id]))
	{
		$Game->boxart = $covers[$Game->id];
	}
}
foreach($lastupdated as $Game)
{
	if(isset($covers[$Game->id]))
	{
		$Game->boxart = $covers[$Game->id];
	}
}
$Game = null;

$Header = new HEADER();
$Header->setTitle("TGDB - Homepage");
$Header->appendRawHeader(function() { ?>
	<meta property="og:title" content="TGDB" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="https://pbs.twimg.com/profile_images/1359389535/favicon_alt_400x400.jpg" />
	<meta property="og:description" content="Welcome to our open, online database for video game fans. Come on over and join our community growing community." />
<?php }); ?>
<?= $Header->print(); ?>

	<div class="container-fluid">

		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-md-10">

				<div class="row">
					<?php foreach($recent as $game) : ?>
					<div class="col-6 col-md-2">
						<a href="/game.php?id=<?= $game->id ?>" style="padding-bottom: 10px;">
							<img alt="<?= $game->game_title?>" class="cover-overlay" src="<?= TGDBUtils::GetCover($game, 'boxart', 'front', true,  true, 'cropped_center_thumb_square') ?>">
							<div class="cover-text-col-3 cover-text cover-text-bottom cover-text-hover"><?= $game->game_title ?></div>
						</a>
					</div>
					<div class="clear"></div>
					<?php endforeach; ?>
				</div>

			</div>
		</div>
		<hr/>

		<div class="row justify-content-center">

			<div class="col-12 col-lg-6 order-2 order-lg-1">
				<h2>Recently Added</h2><hr/>
				<?php foreach($lastupdated as $game) : ?>
				<div class="row" style="padding-bottom:10px">
					<div class="col-3">
						<a href="/game.php?id=<?= $game->id ?>">
							<img alt="<?= $game->game_title?>" class="cover-overlay" src="<?= TGDBUtils::GetCover($game, 'boxart', 'front', true, true, 'thumb') ?>">
						</a>
					</div>
					<div class="col-9">
						<h4><a href="/game.php?id=<?= $game->id ?>"><?= $game->game_title ?></a></h4>
						<h6 class="text-muted">Platform: <?= $Platforms[$game->platform]->name ?></h6>
						<p>
							<?= !empty($game->overview) ? WebUtils::truncate($game->overview, 200) : "No overview is currently available for this title, please feel free to add one."; ?>... <a href="/game.php?id=<?= $game->id ?>">Read More</a></p>
					</div>
				</div>
				<hr/>
				<?php endforeach; ?>
			</div>

			<div class="col-12 col-lg-2 order-1 order-lg-2">

				<div class="card border-secondary mb-3" style="text-align: center;">
					<div class="card-header">
						<h5>Releasing Soon</h5></div>
					<div>
						<table class="table">
							<tbody>
								<?php foreach($soon as $game) : ?>
								<tr>
									<th scope="row">
										<a href="/game.php?id=<?= $game->id ?>">
											<?= $game->game_title ?>
											<br/>
											<span class="text-muted"><?= $Platforms[$game->platform]->name ?></span>
										</a>
									</th>
								</tr>
								<?php endforeach; ?>
							<tbody>
						</table>
					</div>
				</div>

				<iframe style="background-color: transparent;width:100%;height:500px;border:0;" src="https://discordapp.com/widget?id=360271801315491840&theme=light"></iframe>
			</div>

		</div>
		<?= (isset($page)) ? PaginationUtils::Create($has_next_page, '/recently_added.php') : "";?>
	</div>

<?php FOOTER::print(); ?>
