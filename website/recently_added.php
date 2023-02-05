<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\WebUtils;
use TheGamesDB\TGDBUtils;
use TheGamesDB\PaginationUtils;

global $_user;

$API = TGDB::getInstance();
$limit = 18;
$page = PaginationUtils::getPage();
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

foreach($lastupdated as $Game)
{
	if(isset($covers[$Game->id]))
	{
		$Game->boxart = $covers[$Game->id];
	}
}
$Game = null;

$Header = new Header();
$Header->setTitle("TGDB - Homepage");
$Header->appendRawHeader(function() { ?>
	<meta property="og:title" content="TGDB" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="https://pbs.twimg.com/profile_images/1359389535/favicon_alt_400x400.jpg" />
	<meta property="og:description" content="Welcome to our open, online database for video game fans. Come on over and join our community growing community." />
<?php }); ?>
<?= $Header->print(); ?>

	<div class="container">

		<?= (isset($page)) ? PaginationUtils::Create($has_next_page) : "";?>
		<div class="row justify-content-center">

			<div class="col-12 col-lg-12">
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

		</div>
		<?= (isset($page)) ? PaginationUtils::Create($has_next_page) : "";?>

	</div>

<?php Footer::print(); ?>
