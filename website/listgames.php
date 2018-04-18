<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(!isset($_REQUEST['platformID']) || !is_numeric($_REQUEST['platformID']))
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

$API = TGDB::getInstance();
$Platform = $API->GetPlatforms($_REQUEST['platformID'], array("icon" => true, "overview" => true, "developer" => true));
if(isset($Platform[$_REQUEST['platformID']]))
{
	$Platform = $Platform[$_REQUEST['platformID']];
}
else
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
	$errorPage->print_die();
}

$limit = 18;
$page = PaginationUtils::getPage();
$offset = ($page - 1) * $limit;
$list = $API->GetGameListByPlatform($_REQUEST['platformID'], $offset, $limit+1, array(), "GameTitle");
if($has_next_page = count($list) > $limit)
{
	unset($list[$limit]);
}

foreach($list as $Game)
{
	$IDs[] = $Game->id;
}
$covers = $API->GetGameBoxartByID($IDs, 0, 40);
foreach($list as $Game)
{
	if(isset($covers[$Game->id]))
	{
		$Game->boxart = $covers[$Game->id];
	}
}
$Header = new HEADER();
$Header->setTitle("TGDB - Browser - Game By Platform");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">

		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-12 col-md-10">
				<div class="card">
					<div class="card-header">
						<legend><img src="<?= Utils::$BOXART_BASE_URL ?>/consoles/png48/<?= $Platform->icon ?>"> <?= $Platform->name ?></legend>
					</div>
					<div class="card-body">
						<p>Developer: <?= $Platform->developer ?></p>
						<p><?= WebUtils::truncate($Platform->overview, 200, true) ?> <a href="./platform.php?id=<?= $Platform->id ?>">Read More</a></p>
					</div>
				</div>
			</div>
		</div>

		<div class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php foreach($list as $Game) : ?>
			<div class="col-6 col-md-2">
				<div style="padding-bottom:12px; height: 100%">
					<a href="./game.php?id=<?= $Game->id ?>">
						<div class="card border-primary" style="height: 100%">
							<img class="card-img-top" alt="PosterIMG" src="<?= TGDBUtils::GetCover($Game, 'boxart', 'front', true, true, 'thumb') ?>">
							<div class="card-body card-noboday" style="text-align:center;">
							</div>
							<div class="card-footer bg-secondary" style="text-align:center;">
								<p><?= $Game->GameTitle ?></p>
								<p><?= $Game->ReleaseDate ?></p>
							</div>
						</div>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?= PaginationUtils::Create($has_next_page); ?>
	</div>

<?php FOOTER::print(); ?>