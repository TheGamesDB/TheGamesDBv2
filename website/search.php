<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\TGDBUtils;
use TheGamesDB\PaginationUtils;

global $_user;

$search_term = '';
$has_next_page = false;
$list = array();

$API = TGDB::getInstance();
$PlatformList = $API->GetPlatformsList(array("icon" => true));
if(isset($_GET['platform_id']) && !empty($_GET['platform_id']) && !in_array(0, $_GET['platform_id']))
{
	$platformIDs = $_GET['platform_id'];
	foreach($_GET['platform_id'] as $platform_id)
	{
		$platformIDs[$platform_id] = true;
	}
}
if(isset($_GET['name']) && !empty($_GET['name']))
{
	$limit = 18;
	$page = PaginationUtils::getPage();
	$offset = ($page - 1) * $limit;
	if(!isset($_GET['platform_id']) || !is_array($_GET['platform_id']) || in_array(0, $_GET['platform_id']))
	{
		$list = $API->SearchGamesByName($_GET['name'], $offset, $limit + 1);
	}
	else
	{
		$list = $API->SearchGamesByNameByPlatformID($_GET['name'], $_GET['platform_id'], $offset, $limit + 1);
	}
	$search_term = htmlspecialchars($_GET['name']);
	if($has_next_page = count($list) > $limit)
	{
		unset($list[$limit]);
	}

	if(!empty($list))
	{
		foreach($list as $Game)
		{
			$IDs[] = $Game->id;
		}
		$covers = $API->GetGameBoxartByID($IDs, 0, $limit*2);
		foreach($list as $Game)
		{
			if(isset($covers[$Game->id]))
			{
				$Game->boxart = $covers[$Game->id];
			}
		}
		$regionsID = $API->GetRegionsList();
	}
}

$Header = new Header();
$Header->setTitle("TGDB - Search");
?>
<?= $Header->print(); ?>
	<div class="container-fluid">

		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-12 col-md-10">

					<div class="card">
						<form class="card-body" method="get" action="./search.php">
							<fieldset>
							<legend>Search by name</legend>
							<div class="form-group row">
								<label for="name" class="col-sm-2 col-form-label">Name</label>
								<div class="col-sm-10">
								<input name="name" value="<?= $search_term ?>" type="text" class="form-control-plaintext" id="name" placeholder="God Of War...">
								</div>
							</div>
							<div class="form-group">
								<label for="platformselect">Select Platform</label>
								<select name="platform_id[]" multiple class="form-control" id="platformselect">
								<option value="0"  <?= isset($platformIDs) ? "" : "selected" ?>>All</option>
								<?php foreach($PlatformList as $id => $Platform) :?>
								<option value="<?= $id ?>" <?= isset($platformIDs[$id]) ? "selected" : "" ?>><?= $Platform->name ?></option>
								<?php endforeach; ?>
								</select>
							</div>
							<button type="submit" class="btn btn-primary">Submit</button>
							</fieldset>
						</form>
					</div>

			</div>
		</div>

		<div id="display" class="row row-eq-height justify-content-center" style="margin:10px;">
		<?php foreach($list as $Game) : ?>
			<div class="col-6 col-md-2">
				<div style="padding-bottom:12px; height: 100%">
					<a href="./game.php?id=<?= $Game->id ?>">
						<div class="card border-primary" style="height: 100%">
						<img class="card-img-top" alt="<?= $Game->game_title ?> cover" src="<?= TGDBUtils::GetCover($Game, 'boxart', 'front', true, true, 'thumb') ?>">
							<div class="card-body card-noboday" style="text-align:center;">
							</div>
							<div class="card-footer bg-secondary" style="text-align:center;">
								<p><?= $Game->game_title ?></p>
								<?php if ($Game->region_id > 0): ?>
								<p><?= $regionsID[$Game->region_id]->name; ?></p>
								<?php endif; ?>
								<p><?= $Game->release_date ?></p>
								<p class="text-muted"><?= $PlatformList[$Game->platform]->name ?></p>
							</div>
						</div>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?= PaginationUtils::Create($has_next_page); ?>

	</div>

<?php Footer::print(); ?>
