<?php

require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/PaginationUtils.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";


require_once __DIR__ . "/include/login.phpbb.class.php";
$_user = phpBBUser::getInstance();
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
if(isset($_REQUEST['platform_id']) && is_numeric($_REQUEST['platform_id']))
{
	$page = PaginationUtils::getPage();
	$limit = 18;
	$offset = ($page - 1) * $limit;
	$list = $API->GetUserBookmarkedGamesByPlatformID($_user->GetUserID(), $_REQUEST['platform_id'], $offset, $limit + 1);

	if($has_next_page = subarray_item_count($list) > $limit)
	{
		unset($list[$limit]);
	}
}
else
{
	$limit = 6;
	$list = $API->GetUserBookmarkedGamesGroupByPlatform($_user->GetUserID());
}


foreach($list as $platform_id => $per_platform_list)
{
	foreach(array_slice($per_platform_list,0, $limit) as $Game)
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
		foreach(array_slice($per_platform_list,0, $limit) as $Game)
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
$icons = $API->GetPlatformBoxartByID($Platform_IDs, 0, 99999, ['icon']);
foreach($platforms as &$platform)
{
	if(isset($icons[$platform->id]))
	{
		$platform->boxart = &$icons[$platform->id];
	}
}
unset($platform)
$Header = new HEADER();
$Header->setTitle("TGDB - Browser - Game By $listed_by");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<div class="card-body">
						<fieldset>
							<legend>Platforms</legend>
							<div class="grid-container grid-col-config" style=" text-align: center">
								<?php foreach($platforms as $platform) :?>
								<a class="btn btn-link grid-item" href="#platform-<?= $platform->id ?>">
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
			<?php foreach(array_slice($per_platform_list,0, $limit) as $Game) : ?>
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

<?php FOOTER::print(); ?>