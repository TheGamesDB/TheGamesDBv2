<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;

global $_user;

$API = TGDB::getInstance();
$DevsList = $API->GetDevsList();

$alpha_list = array();
foreach($DevsList as $Dev)
{
	$alpha_list[mb_strtoupper(mb_substr($Dev->name, 0, 1))][] = $Dev;
}


$Header = new Header();
$Header->setTitle("TGDB - Browse - Developers");
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
		p
		{
			word-break: break-all;
			white-space: normal;
		}
	</style>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row row-eq-height justify-content-center" style="margin:10px;">
			<div class="col-md-10">
				<div class="card">
					<div class="card-body">
					<?php foreach($alpha_list as $key => $Dev) : ?>
						 <a href="#<?= $key ?>"><?= $key ?></a> 
					<?php endforeach; ?>
					</div>
					<div class="card-body">
						<fieldset>
							<legend>Devs</legend>
						</fieldset>
						<?php foreach($alpha_list as $key => $val_Devlist) : ?>
						<h2 id="<?= $key ?>"><?= $key ?></h2><hr/>
						<div class="grid-container grid-col-config" style=" text-align: center">
							<?php foreach($val_Devlist as $Dev) :?>
							<a class="btn btn-link grid-item" href="./list_games.php?dev_id=<?= $Dev->id ?>">
							<p><?= $Dev->name ?></p>
							</a>
							<?php endforeach; ?>
						</div>
						<hr/>
						<?php endforeach; ?>

					</div>
				</div>
			</div>
		</div>
	</div>

<?php Footer::print(); ?>
