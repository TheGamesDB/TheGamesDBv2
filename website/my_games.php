<?php
require_once __DIR__ . "/include/ErrorPage.class.php";

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


$API = TGDB::getInstance();
$limit = 18;
$page = PaginationUtils::getPage();
$offset = ($page - 1) * $limit;
{
    $listed_by = "My Games";
    //TODO: limit return per platform
	$list = $API->GetUserBookmarkedGamesByPlatform($_user->GetUserID());
}


if($has_next_page = count($list) > $limit)
{
	unset($list[$limit]);
}
foreach($list as $per_platform_list)
{
	foreach($per_platform_list as $Game)
	{
		$IDs[] = $Game->id;
		$Platform_IDs[] = $Game->platform;
	}	
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
$Header = new HEADER();
$Header->setTitle("TGDB - Browser - Game By $listed_by");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php if(isset($list) && !empty($list)) : foreach($list as $per_platform_list) : ?>
			<div class="col-12">
				<br/>
				<h2 style="text-align:center;"><?= $platforms[$per_platform_list[0]->platform]->name ?>(<?= count($per_platform_list) ?>)</h2>
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
		<?= PaginationUtils::Create($has_next_page); ?>
	</div>

<?php FOOTER::print(); ?>