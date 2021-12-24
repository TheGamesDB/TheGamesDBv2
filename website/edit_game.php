<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}
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
require_once __DIR__ . "/include/WebUtils.class.php";


if(isset($_REQUEST['id']) && !empty($_REQUEST['id']) && is_numeric($_REQUEST['id']))
{
	$options = array("release_date" => true, "overview" => true, "players" => true, "rating" => true, "ESRB" => true, "boxart" => true, "coop" => true,
		"genres" => true, "publishers" => true, "platform" => true, "youtube" => true, "alternates" => true, "uids" => true, "region_id" => true, "country_id" => true);
	$API = TGDB::getInstance();
	$GenreList = $API->GetGenres();
	$ESRBRating = $API->GetESRBRating();
	$list = $API->GetGameByID($_REQUEST['id'], 0, 1, $options);
	$PlatformList = $API->GetPlatformsList();
	$RegionList = $API->GetRegionsList();
	$CountryList = $API->GetCountriesList();

	if(empty($list))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_REMOVED_GAME_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
	else
	{
		$Game = array_shift($list);
		$Lock = $API->GetGameLockByID($_REQUEST['id']);
		$covers = $API->GetGameBoxartByID($_REQUEST['id'], 0, 9999, 'ALL');
		if(!empty($covers))
		{
			$Game->boxart = $covers[$_REQUEST['id']];
		}
	}
	$Current_Platform = $API->GetPlatforms($Game->platform, array("icon" => true, "overview" => true, "developer" => true));
	if(isset($Current_Platform[$Game->platform]))
	{
		$Current_Platform = $Current_Platform[$Game->platform];
	}
}

$devs_list = $API->GetDevsList();
$game_devs = $API->GetDevsListByIDs($Game->developers);

$pubs_list = $API->GetPubsList();
$game_pubs = $API->GetPubsListByIDs($Game->publishers);

$fanarts = TGDBUtils::GetAllCovers($Game, 'fanart', '');
$screenshots = TGDBUtils::GetAllCovers($Game, 'screenshot', '');
$banners = TGDBUtils::GetAllCovers($Game, 'banner', '');
$clearlogos = TGDBUtils::GetAllCovers($Game, 'clearlogo', '');
$titlescreens = TGDBUtils::GetAllCovers($Game, 'titlescreen', '');
$is_graphics_empty = empty($fanarts) && empty($screenshots) && empty($banners) &&  empty($clearlogos) && empty($titlescreens);

$box_cover = new \stdClass();
$box_cover->front = TGDBUtils::GetAllCovers($Game, 'boxart', 'front');
if(!empty($box_cover->front))
{
	$box_cover->front = $box_cover->front[0];
}
$box_cover->back = TGDBUtils::GetAllCovers($Game, 'boxart', 'back');
if(!empty($box_cover->back))
{
	$box_cover->back = $box_cover->back[0];
}

$Header = new HEADER();
$Header->setTitle("TGDB - Browse - Game - $Game->game_title");
$Header->appendRawHeader(function() { global $Game, $_user, $game_devs, $devs_list, $game_pubs, $pubs_list; ?>

	<meta property="og:title" content="<?= $Game->game_title; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="<?= !empty($box_cover->front) ? $box_cover->front->thumbnail : "" ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($Game->overview); ?>" />

	<link href="/css/select-pure.css" rel="stylesheet">
	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>

	<script type="text/javascript" src="/js/jquery.fancybox.3.3.5.js"></script>
	<script type="text/javascript" src="/js/fancybox.config.js"></script>
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
			const multi_devs_selected = [
				<?php foreach($game_devs as $dev) : ?> "<?= $dev->id ?>", <?php endforeach; ?>
				];
			multi_devs = new SelectPure('#devs_list', {
				options: multi_devs_selection,
				value: multi_devs_selected,
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			const multi_pubs_selection = [
				<?php foreach($pubs_list as $pub) : ?> { label: "<?= $pub->name ?>", value: "<?= $pub->id ?>" },<?php endforeach; ?>
			];
			const multi_pubs_selected = [
				<?php foreach($game_pubs as $pub) : ?> "<?= $pub->id ?>", <?php endforeach; ?>
				];
			multi_pubs = new SelectPure('#pubs_list', {
				options: multi_pubs_selection,
				value: multi_pubs_selected,
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			<?php if($_user->hasPermission('m_delete_games')) : ?>
			$.fancybox.defaults.btnTpl.del = '<button data-fancybox-del class="fancybox-button fancybox-button--del" title="Delete">' +
				'<svg style="margin: 5px;" enable-background="new 0 0 32 32" id="Layer_1" version="1.1" viewBox="0 0 32 32" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="trash"><path clip-rule="evenodd" d="M29.98,6.819c-0.096-1.57-1.387-2.816-2.98-2.816h-3v-1V3.001   c0-1.657-1.344-3-3-3H11c-1.657,0-3,1.343-3,3v0.001v1H5c-1.595,0-2.885,1.246-2.981,2.816H2v1.183v1c0,1.104,0.896,2,2,2l0,0v17   c0,2.209,1.791,4,4,4h16c2.209,0,4-1.791,4-4v-17l0,0c1.104,0,2-0.896,2-2v-1V6.819H29.98z M10,3.002c0-0.553,0.447-1,1-1h10   c0.553,0,1,0.447,1,1v1H10V3.002z M26,28.002c0,1.102-0.898,2-2,2H8c-1.103,0-2-0.898-2-2v-17h20V28.002z M28,8.001v1H4v-1V7.002   c0-0.553,0.447-1,1-1h22c0.553,0,1,0.447,1,1V8.001z" fill="#333333" fill-rule="evenodd"/><path clip-rule="evenodd" d="M9,28.006h2c0.553,0,1-0.447,1-1v-13c0-0.553-0.447-1-1-1H9   c-0.553,0-1,0.447-1,1v13C8,27.559,8.447,28.006,9,28.006z M9,14.005h2v13H9V14.005z" fill="#333333" fill-rule="evenodd"/><path clip-rule="evenodd" d="M15,28.006h2c0.553,0,1-0.447,1-1v-13c0-0.553-0.447-1-1-1h-2   c-0.553,0-1,0.447-1,1v13C14,27.559,14.447,28.006,15,28.006z M15,14.005h2v13h-2V14.005z" fill="#333333" fill-rule="evenodd"/><path clip-rule="evenodd" d="M21,28.006h2c0.553,0,1-0.447,1-1v-13c0-0.553-0.447-1-1-1h-2   c-0.553,0-1,0.447-1,1v13C20,27.559,20.447,28.006,21,28.006z M21,14.005h2v13h-2V14.005z" fill="#333333" fill-rule="evenodd"/></g></svg>' +
			'</button>';
			fancyboxOpts.buttons.splice(fancyboxOpts.buttons.length -1, 0, "del");
			$('body').on('click', '[data-fancybox-del]', function(instance)
			{
				delete_image($.fancybox.getInstance().current.opts.imageId);
			});
			<?php endif;?>
			fancyboxOpts.share.descr = function(instance, item)
			{
				return "<?= $Game->game_title ?>";
			};
			$('[data-fancybox]').fancybox(fancyboxOpts);

			$("#game_edit").submit(function(e)
			{

				var url = "./actions/edit_game.php"; // the script where you handle the form input.
				var getData = function()
				{
					// disabled elements are not included in post request
					// so we'll enable them to get the data and disable it after
					disabledElm = $('[disabled]');
					disabledElm.prop('disabled', false);
					data = $("#game_edit").serialize()
					disabledElm.prop('disabled', true);
					return data;
				}
				$.ajax({
					type: "POST",
					url: url,
					data: getData() + "&developers%5B%5D=" + multi_devs._config.value.join("&developers%5B%5D=") + "&publishers%5B%5D=" + multi_pubs._config.value.join("&publishers%5B%5D="),
					success: function(data)
					{
						if(isJSON(data))
						{
							var obj = JSON.parse(data);
							if(obj.code == -2)
							{
								// TODO: prompt user to no pub/dev
								// then allow user to procced
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

				e.preventDefault(); // avoid to execute the actual submit of the form.
			});

			$("#game_delete").click(function(e)
			{
				<?php if($_user->hasPermission('m_delete_games')): ?>
				if (confirm('Deleting game record is irreversible, are you sure you want to continue?'))
				{
					var url = "./actions/delete_game.php";
					$.ajax({
						type: "POST",
						url: url,
						data: { game_id: <?= $Game->id ?> },
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
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
				}
				<?php else :?>
				alert("you dont have permission to delete the game, please report it instead, thanks.");
				<?php endif ?>
			});
			<?php if($_user->hasPermission('m_delete_games')) : ?>
				function delete_image(image_id)
				{
					if (confirm('Deleting images record is irreversible, are you sure you want to continue?'))
					{
						var url = "./actions/delete_art.php";
						$.ajax({
							type: "POST",
							url: url,
							data: {
								game_id: <?= $Game->id ?>,
								image_id: image_id
								},
							success: function(data)
							{
								if(isJSON(data))
								{
									var obj = JSON.parse(data);
									if(obj.code == 1)
									{
										alert("image removed please refresh page to see results.");
									}
									else
									{
										alert(data)
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
			<?php endif;?>
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

	<style>
		input.h1
		{
			font-size: 2rem;
			padding: 0.2rem;
		}

		/* Toggle CSS inspired by https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_switch */
		.switch
		{
			position: relative;
			display: inline-block;
			width: 64px;
			height: 100%;
			font-family: "Font Awesome 5 Free";
			font-weight: 900;
		}

		.switch input 
		{
			opacity: 0;
			width: 0;
			height: 0;
		}

		.slider
		{
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: var(--success);
			-webkit-transition: .4s;
			transition: .4s;

		}

		.slider:before
		{
			position: absolute;

			height: 26px;
			width: 26px;
			text-align: center;
			left: calc(50% - 13px);
			bottom:  calc(50% - 13px);
			-webkit-transition: .4s;
			content: "\f09c"

		}

		input:checked + .slider
		{
			background-color:  var(--danger);
		}

		input:focus + .slider
		{
			box-shadow: 0 0 1px #2196F3;
		}

		input:checked + .slider:before
		{
			content: "\f023"
		}

	</style>

	<link href="/css/fine_uploader.5.16.2/fine-uploader-new.css" rel="stylesheet">

	<!-- Fine Uploader jQuery JS file
	====================================================================== -->
	<script src="/js/fine_uploader.5.16.2/jquery.fine-uploader.js"></script>

	<!-- Fine Uploader Thumbnails template w/ customization
	====================================================================== -->
	<script type="text/template" id="qq-template-manual-trigger">
		<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="Drop files here">
			<div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>
				<span class="qq-upload-drop-area-text-selector"></span>
			</div>
			<div class="buttons">
				<div class="qq-upload-button-selector qq-upload-button">
					<div>Select files</div>
				</div>
				<button type="button" id="trigger-upload" class="btn btn-primary">
					<i class="icon-upload icon-white"></i> Upload
				</button>
			</div>
			<div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
				<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
			</div>
			<span class="qq-drop-processing-selector qq-drop-processing">
				<span>Processing dropped files...</span>
				<span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
			</span>
			<ul class="qq-upload-list-selector qq-upload-list" aria-live="polite" aria-relevant="additions removals">
				<li>
					<div class="qq-progress-bar-container-selector">
						<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-progress-bar-selector qq-progress-bar"></div>
					</div>
					<span class="qq-upload-spinner-selector qq-upload-spinner"></span>
					<img class="qq-thumbnail-selector" qq-max-size="100" qq-server-scale>
					<span class="qq-upload-file-selector qq-upload-file"></span>
					<input class="qq-edit-filename-selector qq-edit-filename" tabindex="0" type="text">
					<span class="qq-upload-size-selector qq-upload-size"></span>
					<button type="button" class="qq-btn qq-upload-cancel-selector qq-upload-cancel">Cancel</button>
					<button type="button" class="qq-btn qq-upload-retry-selector qq-upload-retry">Retry</button>
					<button type="button" class="qq-btn qq-upload-delete-selector qq-upload-delete">Delete</button>
					<span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
				</li>
			</ul>

			<dialog class="qq-alert-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">Close</button>
				</div>
			</dialog>

			<dialog class="qq-confirm-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">No</button>
					<button type="button" class="qq-ok-button-selector">Yes</button>
				</div>
			</dialog>

			<dialog class="qq-prompt-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<input type="text">
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">Cancel</button>
					<button type="button" class="qq-ok-button-selector">Ok</button>
				</div>
			</dialog>
		</div>
	</script>
	<script>
		var is_uploading = false;

		$(document).ready(function()
		{
			var fineuploader_config =
			{
				template: 'qq-template-manual-trigger',
				request:
				{
					endpoint: '/actions/uploads.php',
				},
				thumbnails:
				{
					placeholders:
					{
						waitingPath: '/css/fine_uploader.5.16.2/placeholders/waiting-generic.png',
						notAvailablePath: '/css/fine_uploader.5.16.2/placeholders/not_available-generic.png'
					}
				},
				validation:
				{
					itemLimit: <?= WebUtils::$_image_upload_count_limit; ?>,
					acceptFiles: 'image/*',
					allowedExtensions: ['jpe', 'jpg', 'jpeg', 'gif', 'png', 'bmp']
				},
				callbacks:
				{
					onAllComplete: function(succeeded, failed)
					{
						is_uploading = false;
					},
					onUpload : function(id, name)
					{
						is_uploading = true;
						this.setParams(
						{
							game_id : <?= $Game->id ?>,
							type : upload_type,
							subtype : upload_subtype,
						});
					}
				},
				autoUpload: false
			};
			$('#fine-uploader-manual-trigger').fineUploader(fineuploader_config);

			var upload_type = "";
			var upload_subtype = "";
			$('#trigger-upload').click(function()
			{
				$('#fine-uploader-manual-trigger').fineUploader('uploadStoredFiles');
			});

			$('#UploadModal2').on('show.bs.modal', function(event)
			{
				var button = $(event.relatedTarget)
				upload_type = button.data('upload-type')
				upload_subtype = button.data('upload-subtype')

				var modal = $(this)
				modal.find('.modal-title').text('Uploading ' + upload_type + ' ' + upload_subtype)
			})
			// bootstrap doesnt handled nested modal, as such closing the top modal by clicking on the backdrop closes all modal,
			// only closes only 1 backdrop to work around this we have to trigger another hiding
			$('#UploadModal').on('hidden.bs.modal', function(e)
			{
				$('#UploadModal2').modal('hide');
			});
			$('#UploadModal2Button').click(function()
			{
				if(is_uploading)
				{
					alert("Uploading is in progress");
				}
				else
				{
					$("#UploadModal2").modal('hide');
					$('#fine-uploader-manual-trigger').fineUploader('clearStoredFiles');
				}
			});
		});
	</script>
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
						input_field.attr('name', "uids[]");
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

	<form id="game_edit" class="container-fluid">
		<input name="game_id" type="hidden" value="<?= $Game->id ?>"/>
		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
				<?php if(!empty($cover = $fanarts) || !empty($cover = $screenshots)): ?>
					<img class="cover cover-offset" src="<?= $cover[0]->medium ?>"/>
				<?php else: ?>
					<img class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
				<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="row">

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<?php if(!empty($box_cover->front)) : ?>
							<a class="fancybox-thumb" data-image-id="<?= $box_cover->front->id ?>" data-fancybox="cover" data-caption="Front Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
								<?php if(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" data-image-id="<?= $box_cover->back->id ?>" style="display:none;" data-fancybox="cover" data-caption="Back Cover"
								href="<?= $box_cover->back->original ?>" data-thumb="<?= $box_cover->back->thumbnail ?>"/>
							</a>
								<?php endif; ?>
								
							<?php elseif(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" data-image-id="<?= $box_cover->back->id ?>" data-fancybox="cover" data-caption="Back Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php else: ?>
								<img class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage($Game->game_title, 'boxart'); ?>"/>
							<?php endif; ?>
							</a>
							<div class="card-body">
								<?php if($_user->hasPermission('m_delete_games')): ?>
								<p>Platform: <select name="platform" style="width:100%">
												<?php foreach($PlatformList as $Platform) : ?>
												<option value="<?= $Platform->id ?>" <?= ($Current_Platform->id == $Platform->id) ? "selected" : "" ?>><?= $Platform->name ?></option>
												<?php endforeach; ?>
										</select>
								</p>
								<?php else: ?>
								<p>Platform: <a href="/platform.php?id=<?= $Current_Platform->id?>"><?= $Current_Platform->name; ?></a></p>
								<?php endif; ?>
								<p>Region*: 
									<div class="input-group mb-3">
										<select name="region_id" <?= $Lock->region_id && !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> class="form-control">
											<option <?= $Game->region_id == 0 ? 'selected' : '' ?> value="" selected disabled hidden>Select Region</option>
											<?php foreach($RegionList as $region) : ?>
											<option <?= $Game->region_id == $region->id ? 'selected' : '' ?> value="<?= $region->id ?>"><?= $region->name ?></option>
											<?php endforeach; ?>
										</select>
										<div class="input-group-append">
											<label class="switch">
												<input name="region_id_lock" type="checkbox" <?= !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> <?= $Lock->region_id ? 'checked' : ''?>/>
												<span class="slider"></span>
											</label>
										</div>
									</div>
								</p>
								<p>Country:
									<div class="input-group mb-3">
										<select name="country_id" <?= $Lock->country_id && !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> class="form-control">
											<option <?= $Game->country_id == 0 ? 'selected' : '' ?> value="0">No Country</option>
											<?php foreach($CountryList as $country) : ?>
											<option <?= $Game->country_id == $country->id ? 'selected' : '' ?> value="<?= $country->id ?>"><?= $country->name ?></option>
											<?php endforeach; ?>
										</select>
										<div class="input-group-append">
											<label class="switch">
												<input name="country_id_lock" type="checkbox" <?= !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> <?= $Lock->country_id ? 'checked' : '' ?>/>
												<span class="slider"></span>
											</label>
										</div>
									</div>
								</p>
								<p>ReleaseDate*:<br/> <input id="date" name="release_date" type="date" value="<?= $Game->release_date ;?>"></p>
								<p>Players:<br/> <input type="number" name="players"  value="<?= $Game->players; ?>" min="1" max="100"></p>
								</p>
								<p>Co-op:
									<select name="coop">
										<option value="Yes" <?= ($Game->coop == "Yes") ? "selected" : "" ?>>Yes</option>
										<option value="No" <?= ($Game->coop == "No") ? "selected" : "" ?>>No</option>
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
								<div class="input-group mb-3">
									<input name="game_title" <?= $Lock->game_title && !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> type="text" class="h1 form-control" value="<?= $Game->game_title?>"/>
									<div class="input-group-append">
										<label class="switch">
											<input name="game_title_lock" type="checkbox" <?= !$_user->hasPermission('m_delete_games') ? 'disabled' : '' ?> <?= $Lock->game_title ? 'checked' : ''?>/>
											<span class="slider"></span>
										</label>
									</div>
								</div>
								<div id="alts_fields">
									<?php while(!empty($Game->alternates) && !empty($alt_name = array_shift($Game->alternates))) : ?>
									<div class="input-group mb-3">
										<input value="<?= $alt_name ?>" name="alternate_names[]" type="text" class="form-control" placeholder="Alt Name(s)"/>
										<div class="input-group-append">
											<button class="btn btn-danger remove-me-alts" type="button">-</button>
										</div>
									</div>
									<?php endwhile; ?>
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
									<textarea name="overview" rows=10 style="width:100%" placeholder="No overview is currently available for this title, please feel free to add one."><?= !empty($Game->overview) ?
									$Game->overview : "";?></textarea>
								</p>
								<p>YouTube Trailer: <input name="youtube" value="<?= $Game->youtube?>"/></p>
							</div>
							<div class="card-footer">
								<h4>Genres</h4>
								<div class="row">
									<?php foreach($GenreList as $genre) :
										$checked = isset($Game->genres) && !empty($Game->genres) && in_array($genre->id, $Game->genres); ?>
									<div class="col-4" style="margin-bottom:10px;">
										<input name="genres[]" value="<?= $genre->id; ?>" id="genre-<?= $genre->id; ?>" type="checkbox" <?= $checked ? "checked" : "" ?>/>
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
									<?php foreach($ESRBRating as $rate) :
										$checked = strpos($Game->rating, $rate->name) !== false; ?>
									<div class="col-4" style="margin-bottom:10px;">
										<!-- TODO: Rating to Rating[] -->
										<input name="rating" value="<?= $rate->id; ?>" id="rating-<?= $rate->id; ?>" type="radio" <?= $checked ? "checked" : "" ?>/>
										<label for="rating-<?= $rate->id; ?>"><span></span><?= $rate->name; ?></label>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="card-footer">
								<div id="uids_fields">
									<?php while(!empty($Game->uids) && !empty($uid = array_shift($Game->uids))) : ?>
									<div class="input-group mb-3">
										<input value="<?= $uid->uid ?>" name="uids[]" type="text" class="form-control" placeholder="UID(s)"/>
										<div class="input-group-append">
											<button class="btn btn-danger remove-me-uids" type="button">-</button>
										</div>
									</div>
									<?php endwhile; ?>
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
									<?php if(!empty($cover = array_shift($fanarts))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" data-fancybox="fanarts" data-caption="Fanart" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>" alt=""/>
											<img src="/images/ribbonFanarts.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($fanarts)) : ?>
											<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" style="display:none" data-fancybox="fanarts" data-caption="Fanart"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($titlescreens))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" data-fancybox="titlescreens" data-caption="Title Screens" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img src="/images/ribbonTitlescreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($titlescreens)) : ?>
											<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" style="display:none" data-fancybox="titlescreens" data-caption="Title Screen"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($screenshots))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" data-fancybox="screenshots" data-caption="Screenshot" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img src="/images/ribbonScreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($screenshots)) : ?>
											<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" style="display:none" data-fancybox="screenshots" data-caption="Screenshot"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($banners))) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" data-fancybox="banners" data-caption="Banner" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img src="/images/ribbonBanners.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($banners)) : ?>
											<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" style="display:none" data-fancybox="banners" data-caption="Banner"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($clearlogos))) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" data-fancybox="clearlogos" data-caption="Clearlogo" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img src="/images/ribbonClearlogos.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($clearlogos)) : ?>
											<a class="fancybox-thumb" data-image-id="<?= $cover->id ?>" style="display:none" data-fancybox="clearlogos" data-caption="Clearlogo"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if($is_graphics_empty) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<h5>No fanarts/screenshots/banners found, be the 1st to add them.</h5>
									</div>
									<?php endif; ?>
								</div>
							</div>
							
							<div class="card-footer text-right">
								<button type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#UploadModal">Upload Images</button>

								<!-- Modal -->
								<div class="modal fade" id="UploadModal" tabindex="-1" role="dialog" aria-labelledby="UploadModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="UploadModalLabel">Upload Images</h5>
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
												</button>
											</div>
											<div class="modal-body">
												<div class="container-fluid">
													<div class="row justify-content-center">
														<button type="button" data-upload-type="boxart" data-upload-subtype="front" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Front-Cover</button>
														<button type="button" data-upload-type="boxart" data-upload-subtype="back" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Back-Cover</button>
														<button type="button" data-upload-type="fanart" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Fanart</button>
														<button type="button" data-upload-type="screenshot" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Screenshot</button>
														<button type="button" data-upload-type="banner" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Banners</button>
														<button type="button" data-upload-type="clearlogo" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload ClearLogo</button>
														<button type="button" data-upload-type="titlescreen" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Title Screens</button>
													</div>
													<div class="modal fade" id="UploadModal2" tabindex="-1" role="dialog" aria-labelledby="UploadModal2Label"
														aria-hidden="true">
														<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
															<div class="modal-content">
																<div class="modal-header">
																	<h5 class="modal-title" id="UploadModal2Label">Upload</h5>
																	<button id="UploadModal2Button" type="button" class="close" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																	</button>
																</div>
																<div class="modal-body">
																	<div id="fine-uploader-manual-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
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
								<p><button type="submit" class="btn btn-primary btn-block">Save</button></p>
								<?php if($_user->hasPermission('m_delete_games')): ?>
								<p><button id="game_delete" type="button" class="btn btn-danger btn-block">Delete</button></p>
								<?php endif; ?>
								<p><a href="/game.php?id=<?= $Game->id ?>" class="btn btn-default btn-block">Back</a></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</form>

<?php FOOTER::print(); ?>
