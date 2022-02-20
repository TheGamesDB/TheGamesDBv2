<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(!isset($_REQUEST['type']))
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
$PlatformList = $API->GetPlatformsList();
if(isset($_GET['platform_id']) && !empty($_GET['platform_id']) && !in_array(0, $_GET['platform_id']))
{
	$platformIDs = $_GET['platform_id'];
	foreach($_GET['platform_id'] as $platform_id)
	{
		$platformIDs[$platform_id] = true;
	}
}

$limit = 18;
$page = PaginationUtils::getPage();
$offset = ($page - 1) * $limit;
$listed_by = $_REQUEST['type'];
if(isset($listed_by))
{
	if(!isset($_REQUEST['platform_id']) || !is_array($_REQUEST['platform_id']) || in_array(0, $_REQUEST['platform_id']))
	{
		$platform_id = 0;
	}
	else
	{
		$platform_id = $_REQUEST['platform_id'];
	}
	if($_REQUEST['type'] == 'overview')
	{
		$list = $API->GetMissingGames($_REQUEST['type'], $platform_id, $offset, $limit+1, ['platform'], "game_title");
	}
	else
	{
		$sub_type = '';
		if(isset($_REQUEST['sub_type']))
		{
			$sub_type = $_REQUEST['sub_type'];
		}
		$list = $API->GetMissingGamesImages($_REQUEST['type'], $sub_type, $platform_id, $offset, $limit+1, ['platform'], "game_title");
	}
}
else
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_INVALID_PARAM_ERROR);
	$errorPage->print_die();
}



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
	$Platforms = $API->GetPlatforms($PlatformIDs);
	$covers = $API->GetGameBoxartByID($IDs, 0, 40);
	foreach($list as $Game)
	{
		if(isset($covers[$Game->id]))
		{
			$Game->boxart = $covers[$Game->id];
		}
	}
}
$Header = new HEADER();
$Header->setTitle("TGDB - Browser - Game By $listed_by");
?>
<?= $Header->print(); ?>

	<div class="container-fluid">
			<div class="row justify-content-center" style="margin:10px;">
				<div class="col-12 col-md-10">

					<div class="card">
						<form class="card-body" method="get">
							<fieldset>
							<legend>Filter by platform</legend>
							<div class="form-group">
								<label for="platformselect">Select Platform</label>
								<select name="platform_id[]" multiple class="form-control" id="platformselect">
								<option value="0"  <?= isset($platformIDs) ? "" : "selected" ?>>All</option>
								<?php foreach($PlatformList as $id => $Platform) :?>
								<option value="<?= $id ?>" <?= isset($platformIDs[$id]) ? "selected" : "" ?>><?= $Platform->name ?></option>
								<?php endforeach; ?>
								</select>
							</div>
							<input name="type" value="<?= $_REQUEST['type'] ?>" type="hidden" />
							<?php if(!empty($sub_type)): ?>
							<input name="sub_type" value="<?= $sub_type ?>" type="hidden" />
							<?php endif; ?>
							<button type="submit" class="btn btn-primary">Submit</button>
							</fieldset>
						</form>
					</div>

				</div>
			</div>

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
								<p><?= $Platforms[$Game->platform]->name ?></p>
								<p><?= $Game->release_date ?></p>
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
