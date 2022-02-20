<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(
	(!isset($_REQUEST['platform_id']) || !is_numeric($_REQUEST['platform_id']))
	&&
	(!isset($_REQUEST['dev_id']) || !is_numeric($_REQUEST['dev_id']))
	&&
	(!isset($_REQUEST['pub_id']) || !is_numeric($_REQUEST['pub_id']))
	)
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/PaginationUtils.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";

$API = TGDB::getInstance();

$limit = 18;
$page = PaginationUtils::getPage();
$offset = ($page - 1) * $limit;
if(isset($_REQUEST['dev_id']) && is_numeric($_REQUEST['dev_id']))
{
	$listed_by = "Developer";
	$list = $API->GetGamesByDevID($_REQUEST['dev_id'], $offset, $limit+1, array(), "game_title");
	$DevInfo = $API->GetDevsListByIDs($_REQUEST['dev_id']);
	if(!empty($DevInfo))
	{
		$DevInfo = $DevInfo[$_REQUEST['dev_id']];
	}
	else
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
}
else if(isset($_REQUEST['pub_id']) && is_numeric($_REQUEST['pub_id']))
{
	$listed_by = "Publisher";
	$list = $API->GetGamesByPubID($_REQUEST['pub_id'], $offset, $limit+1, array(), "game_title");
	$DevInfo = $API->GetPubsListByIDs($_REQUEST['pub_id']);
	if(!empty($DevInfo))
	{
		$DevInfo = $DevInfo[$_REQUEST['pub_id']];
	}
	else
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
}
else if(isset($_REQUEST['platform_id']) && is_numeric($_REQUEST['platform_id']))
{
	$listed_by = "Platform";
	$Platform = $API->GetPlatforms($_REQUEST['platform_id'], array("icon" => true, "overview" => true, "developer" => true));
	if(isset($Platform[$_REQUEST['platform_id']]))
	{
		$Platform = $Platform[$_REQUEST['platform_id']];
	}
	else
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
	$list = $API->GetGameListByPlatform($_REQUEST['platform_id'], $offset, $limit+1, array(), "game_title");
}
else
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR . " (2)");
	$errorPage->print_die();
}


$PlatformIDs = [];
if($has_next_page = count($list) > $limit)
{
	unset($list[$limit]);
}
foreach($list as $Game)
{
	$IDs[] = $Game->id;
	$PlatformIDs[] = $Game->platform;
}
if(isset($IDs) && !empty($IDs))
{
	$covers = $API->GetGameBoxartByID($IDs, 0, 40);
	foreach($list as $Game)
	{
		if(isset($covers[$Game->id]))
		{
			$Game->boxart = $covers[$Game->id];
		}
	}
}

if(!empty($PlatformIDs))
	$Platforms = $API->GetPlatforms($PlatformIDs);

$Header = new HEADER();
$Header->setTitle("TGDB - Browser - Game By $listed_by");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">
	<?php if(isset($Platform)) : ?>
		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-12 col-md-10">
				<div class="card">
					<div class="card-header">
						<fieldset>
							<legend><img src="<?= CommonUtils::$BOXART_BASE_URL ?>/consoles/png48/<?= $Platform->icon ?>"> <?= $Platform->name ?></legend>
						</fieldset>
					</div>
					<div class="card-body">
						<p>Developer: <?= $Platform->developer ?></p>
						<p><?= WebUtils::truncate($Platform->overview, 200, true) ?> <a href="./platform.php?id=<?= $Platform->id ?>">Read More</a></p>
					</div>
				</div>
			</div>
		</div>
	<?php elseif(isset($DevInfo)) : ?>
		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-12 col-md-10">
				<div class="card">
					<div class="card-header">
						<fieldset>
							<legend><?= $DevInfo->name ?></legend>
						</fieldset>
					</div>
					<div class="card-body">
						<?= $listed_by ?>s overview have not been added yet.
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

		<div class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php if(isset($list) && !empty($list)) : foreach($list as $Game) : ?>
			<div class="col-6 col-md-2">
				<div style="padding-bottom:12px; height: 100%">
					<a href="./game.php?id=<?= $Game->id ?>">
						<div class="card border-primary" style="height: 100%">
							<img class="card-img-top" alt="PosterIMG" src="<?= TGDBUtils::GetCover($Game, 'boxart', 'front', true, true, 'thumb') ?>">
							<div class="card-body card-noboday" style="text-align:center;">
							</div>
							<div class="card-footer bg-secondary" style="text-align:center;">
								<p><?= $Game->game_title ?></p>
								<p><?= $Game->release_date ?></p>
								<p class="text-muted"><?= $Platforms[$Game->platform]->name ?></p>
							</div>
						</div>
					</a>
				</div>
			</div>
		<?php endforeach; else : ?>
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