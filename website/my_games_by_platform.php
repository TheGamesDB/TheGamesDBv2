<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\ErrorPage;
use TheGamesDB\TGDBUtils;
use TheGamesDB\PaginationUtils;

global $_user;

if(!$_user->isLoggedIn())
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
	$errorPage->print_die();
}

function subarray_item_count($array) 
{
	$count = 0;
	foreach($array as $sub_array)
	{
		$count += count($sub_array);
	}

	return $count;
}

$API = TGDB::getInstance();
$listed_by = "My Games";
$page = PaginationUtils::getPage();
$limit = 18;
$offset = ($page - 1) * $limit;
if(isset($_REQUEST['platform_id']) && is_numeric($_REQUEST['platform_id']))
{
	$list = $API->GetUserBookmarkedGamesByPlatformID($_user->GetUserID(), $_REQUEST['platform_id'], $offset, $limit + 1);
	$Platform_IDs = $API->GetUserBookmarkedGamesPlatforms($_user->GetUserID());

	if($has_next_page = subarray_item_count($list) > $limit)
	{
		unset($list[$_REQUEST['platform_id']][$limit]);
	}
}
else
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}


foreach($list as $platform_id => $per_platform_list)
{
	foreach($per_platform_list as $Game)
	{
		$IDs[] = $Game->id;
	}	
	$Platform_IDs[] = $platform_id;
}
if(isset($IDs) && !empty($IDs))
{
	$covers = $API->GetGameBoxartByID($IDs, 0, 9999);
	foreach($list as $per_platform_list)
	{
		foreach($per_platform_list as $Game)
		{
			if(isset($covers[$Game->id]))
			{
				$Game->boxart = $covers[$Game->id];
			}
		}
	}
}
if(isset($Platform_IDs) && !empty($Platform_IDs))
{
	$platforms = $API->GetPlatforms(array_unique($Platform_IDs), ['name']);
}
if(isset($platforms) && count($platforms) > 1)
{
	$icons = $API->GetPlatformBoxartByID($Platform_IDs, 0, 99999, ['icon']);
	foreach($platforms as &$platform)
	{
		if(isset($icons[$platform->id]))
		{
			$platform->boxart = &$icons[$platform->id];
		}
	}
	unset($platform);
}
$Header = new Header();
$Header->setTitle("TGDB - Browser - Game By $listed_by");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<?php if(isset($platforms) && count($platforms) > 1) : ?>
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<div class="card-body">
						<fieldset>
							<legend>Platforms</legend>
							<div class="grid-container grid-col-config" style=" text-align: center">
								<a class="btn btn-link grid-item" href="/my_games.php">
								<img alt="recently added" style="height:48px;padding: 2px;" src="/images/if_recent-time-search-reload-time_2075824.svg">
									<p>Recently added</p>
								</a>
								<?php foreach($platforms as $platform) :?>
								<a class="btn btn-link grid-item" href="/my_games.php?platform_id=<?= $platform->id ?>">
									<img alt="<?= $platform->name?>" src="<?= TGDBUtils::GetCover($platform, 'icon', '', true,  true, 'original') ?>">
									<p><?= $platform->name ?></p>
								</a>
								<?php endforeach; ?>
							</div>
						</fieldset>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php if(isset($list) && !empty($list)) : foreach($list as $platform_id => $per_platform_list) : ?>
			<div class="col-12" id="platform-<?= $platforms[$platform_id]->id ?>">
				<br/>
				<h2 style="text-align:center;">
					<?= $platforms[$platform_id]->name ?><?= (!isset($page)) ? "(" . count($per_platform_list) . ")" : "";?>
					<?php if(!isset($page) && count($per_platform_list) > 6) : ?>
					<a style="text-decoration: underline;" href="/my_games.php?platform_id=<?= $platform_id ?>">More</a>
					<?php endif; ?>
				</h2>
				<hr/>
			</div>
			<?php foreach($per_platform_list as $Game) : ?>
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

		<?php endforeach; endforeach; else : ?>
			<div class="col-12 col-md-10">
				<div class="card">
					<div class="card-body">
						<h3>No associated games.</h3>
					</div>
				</div>
			</div>
		<?php endif; ?>
		</div>
		<?= (isset($page)) ? PaginationUtils::Create($has_next_page) : "";?>
	</div>

<?php Footer::print(); ?>