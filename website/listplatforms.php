<?php
require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";

$API = TGDB::getInstance();
$PlatformList = $API->GetPlatformsList(array("icon" => true));
$Header = new HEADER();
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
						<legend>Platforms</legend>
						<div class="grid-container grid-col-config" style=" text-align: center">
							<?php foreach($PlatformList as  $Platform) :?>
							<a class="btn btn-link grid-item" href="./platform.php?id=<?= $Platform->id ?>">
								<img src="/banners/consoles/png48/<?= $Platform->icon ?>">
								<p><?= $Platform->name ?></p>
							</a>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php FOOTER::print(); ?>
