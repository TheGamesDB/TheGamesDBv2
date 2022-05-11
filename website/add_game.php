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
	if(!$_user->hasPermission('u_edit_games'))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
		$errorPage->print_die();
	}
}

require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";


$API = TGDB::getInstance();
$PlatformList = $API->GetPlatformsList();
$RegionList = $API->GetRegionsList();
$CountryList = $API->GetCountriesList();
$GenreList = $API->GetGenres();
$ESRBRating = $API->GetESRBRating();
$devs_list = $API->GetDevsList();
$pubs_list = $API->GetPubsList();

$Header = new HEADER();
$Header->setTitle("TGDB - Add Game");
$Header->appendRawHeader(function() { global $devs_list, $pubs_list; ?>

	<link href="/css/select-pure.css" rel="stylesheet">
	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" src="/js/pure-select.modded.0.6.2.js"></script>


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

		$(document).ready(function()
		{
			const multi_devs_selection = [
				<?php foreach($devs_list as $dev) : ?> { label: "<?= $dev->name ?>", value: "<?= $dev->id ?>" },<?php endforeach; ?>
			];
			multi_devs = new SelectPure('#devs_list', {
				options: multi_devs_selection,
				value: [],
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			const multi_pubs_selection = [
				<?php foreach($pubs_list as $pub) : ?> { label: "<?= $pub->name ?>", value: "<?= $pub->id ?>" },<?php endforeach; ?>
			];
			multi_pubs = new SelectPure('#pubs_list', {
				options: multi_pubs_selection,
				value: [],
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			var add_game = function(e)
			{
				var url = "./actions/add_game.php";

				$.ajax({
					type: "POST",
					url: url,
					data: $("#game_add").serialize() + "&developers%5B%5D=" + multi_devs._config.value.join("&developers%5B%5D=") + "&publishers%5B%5D=" + multi_pubs._config.value.join("&publishers%5B%5D="),
					success: function(data)
					{
						if(isJSON(data))
						{
							var obj = JSON.parse(data);
							if(obj.code == 1)
							{
								alert(data)
								window.location.href="<?= CommonUtils::$WEBSITE_BASE_URL ?>/game.php?id=" + obj.msg;
								return;
							}
							alert(data)
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
			};
			$("#game_add").submit(function(e)
			{
				var url = "./actions/game_search_count.php";
				$.ajax({
					type: "POST",
					url: url,
					data: $("[name='game_title']").serialize(),
					success: function(data)
					{
						if(isJSON(data))
						{
							var obj = JSON.parse(data);
							if(obj.code == 1)
							{
								if(obj.msg == 0)
								{
									add_game();
								}
								else if(obj.msg > 0)
								{
									if(confirm(obj.msg + " Other games were found with similair title, please ensure that this title has not been added yet, Thanks.\nare you sure you want to continue?"))
									{
										add_game();
									}
								}
								else
								{
									alert("Error: Something Unexpected Happened.");
								}
							}
							return;
						}
						else
						{
							alert("Error: Something Unexpected Happened.");
							return;
						}
					}
				});

				e.preventDefault();
			});
		});
	</script>
	<style type="text/css">
		.cover
		{
			width: 100%;
			position: relative;
		}
		
		@media screen and (min-width: 800px)
		{
			.cover-offset
			{
				margin-top: <?= isset($_REQUEST['test']) ? "-250px" : "-170px" ?>;
			}
			.fanart-banner
			{
				max-height: 100%;
				height: <?= isset($_REQUEST['test']) ? "200px" : "325px" ?>;
				overflow: hidden;
				text-align: center;
			}
		}

		@media screen and (max-width: 800px)
		{
			.cover-offset
			{
				margin-top: 0;
			}

			.fanart-banner
			{
				max-height: 100%;
				height: 175px;
				overflow: hidden;
				text-align: center;
			}
		}
		.margin5px
		{
			margin: 5px;
		}
		input[type="checkbox"]
		{
			position: absolute;
		}
		input[type="checkbox"] ~ label
		{
			text-overflow: ellipsis;
			display: inline-block;
			overflow: hidden;
			width: 90%;
			white-space: nowrap;
			vertical-align: middle;
			margin-left: 1.2rem;
		}
	</style>

	<script type="text/template" id="template-multi-field">
		<div class="input-group mb-3">
			<input id="field" name="field[]" type="text" class="form-control" placeholder="Alt Name(s)"/>
			<div class="input-group-append">
				<button class="btn btn-success" type="button">+</button>
			</div>
		</div>
	</script>
	<script>
		$(document).ready(function()
		{
			function remove_me(type)
			{
				$('.remove-me-' + type).click(function(e)
				{
					e.preventDefault();
					$(this).parent().parent().remove();
				});
			}
			function add_more(type)
			{
				$(".add-more-" + type).click(function(e){
					e.preventDefault();

					var ele = $($.trim($('#template-multi-field').clone().html()));
					ele.find(".btn").addClass("add-more-" + type);
					input_field = ele.find("#field");
					if(type == "uids")
					{
						input_field.attr('name', "uids");
						input_field.attr('placeholder', 'UID(s)');
					}
					else
					{
						input_field.attr('name', "alternate_names[]");
						input_field.attr('placeholder', 'Alt Name(s)');
					}

					$("#" + type + "_fields").append(ele);

					$(this).removeClass("btn-success add-more-" + type).addClass("btn-danger remove-me-" + type);
					$(this).text("-");

					$(".add-more-" + type + ", .remove-me-" + type).unbind("click");

					add_more(type);
					remove_me(type);
				});
			}
			add_more("alts");
			remove_me("alts");
			add_more("uids");
			remove_me("uids");
		});

</script>
<?php });?>
<?= $Header->print(); ?>

	<form id="game_add" class="container-fluid">
		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
					<img class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
				</div>
			</div>
		</div>

		<div class="row">

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<img class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage("Placeholder", 'boxart'); ?>"/>
							<div class="card-body">
								<p>Platform: <select name="platform" style="width:100%">
										<option value="" selected disabled hidden>Select Platform</option>
										<?php foreach($PlatformList as $Platform) : ?>
										<option value="<?= $Platform->id ?>"><?= $Platform->name ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>Region*: <select name="region_id" style="width:100%">
										<option value="" selected disabled hidden>Select Region</option>
										<?php foreach($RegionList as $region) : ?>
										<option value="<?= $region->id ?>"><?= $region->name ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>Country: <select name="country_id" style="width:100%">
										<option value="0">No Country</option>
										<?php foreach($CountryList as $country) : ?>
										<option value="<?= $country->id ?>"><?= $country->name ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>ReleaseDate*:<br/> <input id="date" name="release_date" type="date" value="1970-01-01"></p>
								<p>Players:<br/> <input type="number" name="players" min="1" max="100"></p>
								<p>Co-op:
									<select name="coop">
										<option value="Yes">Yes</option>
										<option value="No" selected>No</option>
									</select>
								</p>
								<p>* : safari doesnt support calender input yet, so please keep date format to (yyyy-mm-dd)</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-12 col-md-9 col-lg-8">
				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1><input style="width:100%" name="game_title" placeholder="GameTitle goes here..."/></h1>
								<div id="alts_fields">
									<div class="input-group mb-3">
										<input name="alternate_names[]" type="text" class="form-control" placeholder="Alt Name(s)"/>
										<div class="input-group-append">
											<button class="btn btn-success add-more-alts" type="button">+</button>
										</div>
									</div>
								</div>
							</div>
							<div class="card-body">
								<p>
									<textarea name="overview" rows=10 style="width:100%" placeholder="No overview is currently available for this title, please feel free to add one."></textarea>
								</p>
								<p>YouTube Trailer: <input name="youtube"/></p>
							</div>
							<div class="card-footer">
								<h4>Genres</h4>
								<div class="row">
									<?php foreach($GenreList as $genre) : ?>
									<div class="col-4" style="margin-bottom:10px;">
										<input name="genres[]" value="<?= $genre->id; ?>" id="genre-<?= $genre->id; ?>" type="checkbox" />
										<label for="genre-<?= $genre->id; ?>"><span></span><?= $genre->name; ?></label>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="card-footer">
								<h4>Developer(s)</h4>
								<p><span id="devs_list"></span></p>
							</div>
							<div class="card-footer">
								<h4>Publisher(s)</h4>
								<p><span id="pubs_list"></span></p>
							</div>
							<div class="card-footer">
								<h4>ESRB Rating</h4>
								<div class="row">
									<?php foreach($ESRBRating as $rate) : ?>
									<div class="col-4" style="margin-bottom:10px;">
										<input name="rating" value="<?= $rate->id; ?>" id="rating-<?= $rate->id; ?>" type="radio" />
										<label for="rating-<?= $rate->id; ?>"><span></span><?= $rate->name; ?></label>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="card-footer">
								<div id="uids_fields">
									<div class="input-group mb-3">
										<input name="uids[]" type="text" class="form-control" placeholder="UID(s)"/>
										<div class="input-group-append">
											<button class="btn btn-success add-more-uids" type="button">+</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<?php if (true) : ?>
				<div class="row" style="padding-bottom:10px;">
					<div class="col">
						<div class="card border-primary">
							<h3 class="card-header">
								Other Graphic(s)
							</h3>
							<div class="card-body">
								<div class="row justify-content-center">
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<h5>You can add fanarts/screenshots/banners found, after the game is added.</h5>
									</div>
								</div>
							</div>
							
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<legend>Control Panel</legend>
							</div>
							<div class="card-body">
								<p><button type="submit" class="btn btn-success btn-block">Add</button></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</form>

<?php FOOTER::print(); ?>
