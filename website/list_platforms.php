<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\TGDBUtils;

global $_user;

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
$Header = new Header();
$Header->setTitle("TGDB - Browse - Platforms");
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
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<div class="card-body">
						<fieldset>
							<legend>Platforms</legend>
							<div class="grid-container grid-col-config" style=" text-align: center">
								<?php foreach($PlatformList as  $Platform) :?>
								<a class="btn btn-link grid-item" href="./platform.php?id=<?= $Platform->id ?>">
									<img alt="<?= $Platform->name?>" src="<?= TGDBUtils::GetCover($Platform, 'icon', '', true,  true, 'original') ?>">
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

<?php Footer::print(); ?>
