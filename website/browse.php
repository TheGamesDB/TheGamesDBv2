<?php
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";

$API = TGDB::getInstance();
$PlatformList = $API->GetPlatformsList(array("icon" => true));

$PlatformIDs = array();
foreach($PlatformList as &$platform)
{
	$PlatformIDs[] = $platform->id;
}
$icons = $API->GetPlatformBoxartByID($PlatformIDs, 0, 99999, ['icon']);
foreach($PlatformList as &$platform)
{
	if(isset($icons[$platform->id]))
	{
		$platform->boxart = &$icons[$platform->id];
	}
}
$Header = new HEADER();
$Header->setTitle("TGDB - Browser");
$Header->appendRawHeader(function()
{ ?>
	<style>
		.grid-container
		{
			display: grid;
			grid-gap: 5px;
		}

		.grid-col-config
		{
			grid-template-columns: auto auto auto auto;
		}

		@media screen and (max-width: 767px)
		{
			.grid-col-config
			{
				grid-template-columns: auto auto;
			}
		}

		@media screen and (max-width: 321px)
		{
			.grid-col-config
			{
				grid-template-columns: auto;
			}
		}

		.grid-item
		{
			border: 1px solid rgba(0, 0, 0, 0.8);
			border-radius: 5px;
			padding: 12px;
			text-align: center;
		}
	</style>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<form class="card-body" method="get" action="./search.php">
						<fieldset>
							<legend>Search by name</legend>
							<div class="form-group row">
								<label for="name" class="col-sm-2 col-form-label">Name</label>
								<div class="col-sm-10">
								<input name="name" type="text" class="form-control-plaintext" id="name" placeholder="God Of War...">
								</div>
							</div>
							<div class="form-group">
								<label for="platformselect">Select Platform</label>
								<select name="platform_id[]" multiple class="form-control" id="platformselect">
								<option value="0" selected>All</option>
								<?php foreach($PlatformList as $id => $Platform) :?>
								<option value="<?= $id ?>"><?= $Platform->name ?></option>
								<?php endforeach; ?>
								</select>
							</div>
							<button type="submit" class="btn btn-primary">Submit</button>
						</fieldset>
					</form>
				</div>
			</div>
		</div>

		<div class="row justify-content-center" style="margin:10px;">
			<h1 style="font-size:4rem">OR</h1>
		</div>

		<div class="row justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<div class="card-body">
						<fieldset>
							<legend>Browse by platform</legend>
							<div class="grid-container grid-col-config" style=" text-align: center">
								<?php foreach($PlatformList as $id => $Platform) :?>
								<a class="btn btn-link grid-item" href="./list_games.php?platform_id=<?= $id ?>">
									<img alt="<?= $Platform->name ?>" src="<?= TGDBUtils::GetCover($Platform, 'icon', '', true,  true, 'original') ?>">
									<p><?= $Platform->name ?></p>
								</a>
								<?php endforeach; ?>
							</div>
						</fieldset>
					</div>
				</div>
			</div>
		</div>

	</div>

<?php FOOTER::print(); ?>
