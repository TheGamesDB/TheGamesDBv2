<?php
if(isset($_REQUEST['platform_id']) && is_numeric($_REQUEST['platform_id']))
{
	include "my_games_by_platform.php";
	die();
}
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/PaginationUtils.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";
require_once __DIR__ . "/include/ErrorPage.class.php";


require_once __DIR__ . "/include/login.common.class.php";
$_user = phpBBUser::getInstance();
if(!$_user->isLoggedIn())
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
	$errorPage->print_die();
}


$API = TGDB::getInstance();
$page = PaginationUtils::getPage();
$limit = 18;
$offset = ($page - 1) * $limit;
$list = $API->GetUserBookmarkedGames($_user->GetUserID(), $offset, $limit + 1);
$Platform_IDs = $API->GetUserBookmarkedGamesPlatforms($_user->GetUserID());
$listed_by = "My Games";

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

$platforms = [];
if(isset($Platform_IDs) && !empty($Platform_IDs))
{
	$platforms = $API->GetPlatforms(array_unique($Platform_IDs), ['name']);
	$icons = $API->GetPlatformBoxartByID($Platform_IDs, 0, 99999, ['icon']);
	foreach($platforms as &$platform)
	{
		if(isset($icons[$platform->id]))
		{
			$platform->boxart = &$icons[$platform->id];
		}
	}
}
unset($platform);
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
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php if(isset($list) && !empty($list)): ?>
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
									<p class="text-muted"><?= $platforms[$Game->platform]->name ?></p>
								</div>
							</div>
						</a>
					</div>
				</div>

		<?php endforeach; else : ?>
			<div class="col-12 col-md-10">
				<div class="card">
					<div class="card-body">
						<h3>Please add games to your collection first.</h3>
					</div>
				</div>
			</div>
		<?php endif; ?>
		</div>
		<?= (isset($page)) ? PaginationUtils::Create($has_next_page) : "";?>
	</div>

<?php FOOTER::print(); ?>