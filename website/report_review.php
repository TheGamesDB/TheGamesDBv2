<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
require_once __DIR__ . "/include/login.common.class.php";
$_user = phpBBUser::getInstance();
if(!$_user->isLoggedIn())
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
	$errorPage->print_die();
}
else
{
	if(!$_user->hasPermission('m_delete_games'))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
		$errorPage->print_die();
	}
}

require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/include/WebUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";

$BASE_URL = CommonUtils::getImagesBaseURL();

$API = TGDB::getInstance();
$offset = 0;
$limit = 100;
$reports = $API->GetGamesReports(0, $offset, $limit);
foreach($reports as $Game)
{
	$IDs[] = $Game->games_id;
	if($Game->type == 1)
	{
		$additional_games_id[] = $Game->metadata_0;
	}
	$PlatformIDs[] = $Game->platform;
}
if(isset($additional_games_id))
{
	$additional_games = $API->GetGameByID($additional_games_id, $offset, $limit);
}
if(isset($IDs))
{
	$games = $API->GetGameByID($IDs, $offset, $limit);
}

foreach($additional_games as $Game)
{
	$IDs[] = $Game->id;
	$PlatformIDs[] = $Game->platform;
}
$Platforms = $API->GetPlatforms($PlatformIDs, $offset, $limit);
$covers = $API->GetGameBoxartByID($IDs, 0, 9999);
foreach($reports as $Game)
{
	if(isset($covers[$Game->games_id]))
	{
		$Game->boxart = $covers[$Game->games_id];
	}
}

foreach($additional_games as &$Game)
{
	$ref_additional_games[$Game->id] = $Game;
	if(isset($covers[$Game->id]))
	{
		$Game->boxart = $covers[$Game->id];
	}
}
foreach($games as &$Game)
{
	$ref_games[$Game->id] = $Game;
}

$Game = null;

function PrintViews(&$report)
{
	switch($report->type)
	{
		case 1:
			PrintDuplicateView($report);
		break;
	}
}

function PrintDuplicateView(&$report)
{ global $ref_additional_games, $ref_games, $Platforms;
	if(!isset($ref_additional_games[$report->metadata_0]) || !isset($ref_games[$report->games_id]))
	{
		//TODO: mark as resolved
		return;
	}
	?>
			<div id="report_<?= $report->id ?>" class="col-12 col-lg-8 order-2 order-lg-1 text-center">
				<div class="row" style="padding-bottom:10px;align-items: center;">
					<div class="col-3">
						<a href="/game.php?id=<?= $report->games_id ?>">
							<img alt="<?= $report->game_title ?>" class="cover-overlay"src="<?= TGDBUtils::GetCover($report, 'boxart', 'front', true, true, 'thumb') ?>">
						</a>
					</div>
					<div class="col-6">
						<div>
							<a style="font-weight: bold;" href="https://forums.thegamesdb.net/memberlist.php?mode=viewprofile&u=<?= $report->user_id ?>"><?= $report->username ?></a> reports the following game
							<br/>
							<a style="font-weight: bold;" href="/game.php?id=<?= $report->games_id ?>"><?= $report->game_title . "(games_id: $report->games_id)" ?></a>
							<br/>as a duplicate of<br/>
							<a style="font-weight: bold;" href="/game.php?id=<?= $report->metadata_0 ?>"><?= $ref_additional_games[$report->metadata_0]->game_title . " (games_id: $report->metadata_0)" ?></a>
						</div>
						<br/>
						<div class="row justify-content-center" style="padding-bottom:10px;">
							<div class="col-4">
								<button type="button"  data-type="delete" data-report-id="<?= $report->id ?>" data-game-id="<?= $report->games_id ?>" class="btn btn-danger">&lt;-- Delete</button>
							</div>
							<div class="col-4">
								<button type="button"  data-type="delete" data-report-id="<?= $report->id ?>" data-game-id="<?= $report->metadata_0 ?>" class="btn btn-danger">Delete --&gt;</button>
							</div>
						</div>
						<div class="row justify-content-center" style="padding-bottom:10px;">
							<div class="col-8">
								<button type="button" data-type="resolve" data-report-id="<?= $report->id ?>" class="btn btn-primary">Mark as resolved</button>
							</div>
						</div>
					</div>
					<div class="col-3">
						<a href="/game.php?id=<?= $report->metadata_0 ?>">
							<img alt="<?= $ref_additional_games[$report->metadata_0]->game_title ?>" class="cover-overlay"src="<?= TGDBUtils::GetCover($ref_additional_games[$report->metadata_0], 'boxart', 'front', true, true, 'thumb') ?>">
						</a>
					</div>
				</div>

				<div class="row" style="padding-bottom:10px">
					<div class="col-3">
						<h6><a href="/game.php?id=<?= $report->games_id ?>"><?= $report->game_title ?></a></h6>
						<p class="text-muted">Platform: <?= $Platforms[$report->platform]->name ?></p>
					</div>
					<div class="col-6">
					</div>
					<div class="col-3">
						<h6><a href="/game.php?id=<?= $ref_additional_games[$report->metadata_0]->games_id ?>"><?= $ref_additional_games[$report->metadata_0]->game_title ?></a></h6>
						<p class="text-muted">Platform: <?= $Platforms[$ref_additional_games[$report->metadata_0]->platform]->name ?></p>
					</div>
				</div>
				<hr/>
			</div>
	<?php
}

$Header = new HEADER();
$Header->setTitle("TGDB - Games Reports");
$Header->appendRawHeader(function() { ?>
	<script type="text/javascript">
		function isJSON(json)
		{
			try
			{
				return (JSON.parse(json) && !!json);
			}
			catch (e)
			{
				return false;
			}
		}
		function resolve_report(report_id)
		{
			{
				var url = "./actions/resolve_game_report.php";
				$.ajax({
					type: "POST",
					url: url,
					data: { id: report_id },
					success: function(data)
					{
						if(isJSON(data))
						{
							alert(data);
							var obj = JSON.parse(data);
							if(obj.code == 1)
							{
								$('#report_' + report_id).animate({ height: 0, opacity: 0 }, 'slow', function() { $('#report_' + report_id).remove();});
							}
							return;
						}
						else
						{
							alert("Error: Something Unexpected Happened.")
							return;
						}
					}
				});
			}
		}
		$(document).ready(function()
		{
			$('[data-type="resolve"]').on('click', function(e)
			{
				if (confirm('Do you want to mark this as resolved?'))
				{
					var report_id = $(this).data().reportId;
					resolve_report(report_id);
					e.preventDefault();
				}
			});
			$('[data-type="delete"]').on('click', function(e)
			{
				if (confirm('Deleting game record is irreversible, are you sure you want to continue?'))
				{
					return;
					var url = "./actions/delete_game.php";
					var games_id = $(this).data().gameId;
					var report_id = $(this).data().reportId;
					$.ajax({
						type: "POST",
						url: url,
						data: { game_id: games_id },
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
								if(obj.code == 1)
								{
									resolve_report(report_id);
								}
								else
								{
									alert(data);
								}
								return;
							}
							else
							{
								alert("Error: Something Unexpected Happened.")
								return;
							}
						}
					});
					e.preventDefault();
				}
			});
		});
</script>
<?php
});?>

<?= $Header->print(); ?>

	<div class="container-fluid">
		<div class="row justify-content-center">

			<div class="col-12 col-lg-8 order-2 order-lg-1 text-center">
				<h2>Reports(<?= count($reports) ?>)</h2><hr/>
			</div>
			<?php foreach($reports as $game) 
			{
				PrintViews($game);
			} ?>
		</div>

	</div>

<?php FOOTER::print(); ?>
