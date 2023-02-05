<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\TGDBUtils;

global $_user;

$API = TGDB::getInstance();

if($_user->isLoggedIn() && $_user->hasPermission('m_delete_games') && isset($_REQUEST['update']))
{
	$API->UpdateStats();
}
$Stats = $API->GetGamesStats();

$limit = 75;
$list = $API->GetLatestGameBoxartStats($limit);

$PsudoGame = new stdClass();
$PsudoGame->boxart = $list;

$fanarts = TGDBUtils::GetAllCovers($PsudoGame, 'fanart', '');
$screenshots = TGDBUtils::GetAllCovers($PsudoGame, 'screenshot', '');
$banners = TGDBUtils::GetAllCovers($PsudoGame, 'banner', '');
$clearlogos = TGDBUtils::GetAllCovers($PsudoGame, 'clearlogo', '');
$titlescreen = TGDBUtils::GetAllCovers($PsudoGame, 'titlescreen', '');
$fboxart = TGDBUtils::GetAllCovers($PsudoGame, 'boxart', 'front');
$bboxart = TGDBUtils::GetAllCovers($PsudoGame, 'boxart', 'back');

foreach($PsudoGame->boxart as $image)
{
	$IDs[] = $image->game_id;
}
$Games = array();
{
	$tmp_Games = $API->GetGameByID($IDs, 0, $limit*6);
	foreach($tmp_Games as $game)
	{
		$Games[$game->id] = $game;
	}
	$tmp_Games = null;
}

$Header = new Header();
$Header->setTitle("TGDB - Statistics");
$Header->appendRawHeader(function() { global $PsudoGame; ?>
	<script type="text/javascript" src="/js/Chart.2.7.2.js"></script>
	<script type="text/javascript"  src="/js/jquery.fancybox.3.3.5.js"></script>
	<script type="text/javascript" src="/js/fancybox.config.js"></script>	
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

		<script type="text/javascript">
			$(document).ready(function()
			{
				var index = fancyboxOpts.buttons.indexOf("share");
				if (index > -1)
				{
					fancyboxOpts.buttons.splice(index, 1);
				}
				$('[data-fancybox]').fancybox(fancyboxOpts);
			});
		</script>
<?php });?>
<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row justify-content-center">
			<div class="col-12 col-md-9 col-lg-8">

				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1>stats</h1>
							</div>
							<div class="card-body bg-light">
								<canvas id="myChart"></canvas>
								<script>
									var ctx = document.getElementById("myChart").getContext('2d');
									var myChart = new Chart(ctx, {
										type: 'bar',
										data: {
											labels: [
												<?php foreach($Stats as $key => $count) : if($key == 'total') continue;?>
												"<?= $key ?>",
												<?php endforeach; ?>
											],
											datasets: [{
												label: '% of Completed Games',
												data: [
													<?php foreach($Stats as $key => $count) : if($key == 'total') continue;?>
													"<?= round($count / $Stats['total'] * 100, 2) ?>",
													<?php endforeach; ?>
												],
												backgroundColor: [
													'rgba(255, 99, 132, 0.2)',
													'rgba(54, 162, 235, 0.2)',
													'rgba(255, 206, 86, 0.2)',
													'rgba(75, 192, 192, 0.2)',
													'rgba(153, 102, 255, 0.2)',
													'rgba(255, 159, 64, 0.2)',

													'rgba(25, 9, 12, 0.2)',
													'rgba(154, 12, 25, 0.2)',
													'rgba(25, 126, 61, 0.2)',
													'rgba(175, 92, 112, 0.2)',
												],
												borderColor: [
													'rgba(255,99,132,1)',
													'rgba(54, 162, 235, 1)',
													'rgba(255, 206, 86, 1)',
													'rgba(75, 192, 192, 1)',
													'rgba(153, 102, 255, 1)',
													'rgba(255, 159, 64, 1)'
												],
												borderWidth: 1
											}]
										},
										options: {
											scales: {
												yAxes: [{
													ticks: {
														beginAtZero: true,
														min: 0,
														max: 100,
														callback: function(value) {
															return value + "%"
														}
													},
													scaleLabel: {
														display: true,
														labelString: "Percentage"
													}
												}]
											}
										}
									});
								</script>
							</div>
						</div>
					</div>
				</div>

				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1>Recent Additions</h1>
							</div>
							<div class="card-body bg-light">
								<div class="row justify-content-center" style="text-align:center;">
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<?php if(!empty($cover = array_shift($fboxart))) : ?>
											<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="fboxart" data-caption="Front Cover - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Front Covers
											</a>
											<?php while($cover = array_shift($fboxart)) : ?>
												<a class="fancybox-thumb" style="display:none" data-fancybox="fboxart" data-caption="Front Cover - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
												<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($bboxart))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="bboxart" data-caption="Back Cover - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Back Covers
										</a>
										<?php while($cover = array_shift($bboxart)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="bboxart" data-caption="Back Cover - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($fanarts))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="fanarts" data-caption="Fanart - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Fanarts
										</a>
										<?php while($cover = array_shift($fanarts)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="fanarts" data-caption="Fanart - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($titlescreen))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="titlescreen" data-caption="Title Screen - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Title Screen
										</a>
										<?php while($cover = array_shift($titlescreen)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="titlescreen" data-caption="Title Screen - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($screenshots))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="screenshots" data-caption="Screenshot - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Screenshots
										</a>
										<?php while($cover = array_shift($screenshots)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="screenshots" data-caption="Screenshot - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($banners))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="banners" data-caption="Banner - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest Banners
										</a>
										<?php while($cover = array_shift($banners)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="banners" data-caption="Banner - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($clearlogos))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-info btn-wrap-text fancybox-thumb" data-fancybox="clearlogos" data-caption="ClearLogo - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>" alt=""> Latest ClearLogos
										</a>
										<?php while($cover = array_shift($clearlogos)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="clearlogos" data-caption="ClearLogo - <a href='/game.php?id=<?= $cover->game_id ?>' target='_black'><?= $Games[$cover->game_id]->game_title ?></a>" href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
											<?php endwhile; ?>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1>Help us fill-in the database</h1>
							</div>
							<div class="card-body bg-light">
								<div class="row justify-content-center" style="text-align:center;">
									<div class="col-12 col-sm-12" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=overview'>
											Missing Overviews
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=boxart&sub_type=front'>
											Missing Front Covers
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=boxart&sub_type=back'>
											Missing Back Covers
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=fanart'>
											Missing Fanarts
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=titlescreen'>
											Missing Title Screen
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=screenshot'>
											Missing Screenshots
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=banner'>
											Missing Banners
										</a>
									</div>
									<div class="col-12 col-sm-6" style="margin-bottom:10px;">
										<a class="btn btn-block btn-danger btn-wrap-text" href='/missing.php?type=clearlogo'>
											Missing ClearLogos
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

<?php Footer::print(); ?>
