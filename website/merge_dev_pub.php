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
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";

$API = TGDB::getInstance();
$devs_list = $API->GetDevsList();
$pubs_list = $API->GetPubsList();

$Header = new HEADER();
$Header->setTitle("TGDB - Merge");
$Header->appendRawHeader(function() { global $_user, $devs_list, $pubs_list; ?>

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
				<?php foreach($devs_list as $dev) : ?> { label: "<?= $dev->name ?> (ID: <?= $dev->id ?>)", value: "<?= $dev->id ?>" },<?php endforeach; ?>
			];

			keep_multi_devs = new SelectPure('#devs_list_keep', {
				options: multi_devs_selection,
				autocomplete: true,
				multiple: false,
				icon: "fas fa-times",
			});
			remove_multi_devs = new SelectPure('#devs_list_remove', {
				options: multi_devs_selection,
				autocomplete: true,
				multiple: false,
				icon: "fas fa-times",
			});

			const multi_pubs_selection = [
				<?php foreach($pubs_list as $pub) : ?> { label: "<?= $pub->name ?>(ID: <?= $pub->id ?>)", value: "<?= $pub->id ?>" },<?php endforeach; ?>
			];
			keep_multi_pubs = new SelectPure('#pubs_list_keep', {
				options: multi_pubs_selection,
				autocomplete: true,
				multiple: false,
				icon: "fas fa-times",
			});
			remove_multi_pubs = new SelectPure('#pubs_list_remove', {
				options: multi_pubs_selection,
				autocomplete: true,
				multiple: false,
				icon: "fas fa-times",
			});
			


			<?php if($_user->hasPermission('m_delete_games')) : ?>
				function getInput(tbl_name, keep, remove, var_url)
				{
					return {
						type: "POST",
						url: "./actions/merge_dev_pub.php",
						data: "tbl=" + tbl_name + "&keep=" + keep + "&remove=" + remove,
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
								if(obj.code == -2)
								{
									alert("selected " + tbl_name + " don't exist, reloading page.");
									location.reload(true);
								}
								else if(obj.code == 0)
								{
									alert("Success")
									location.reload(true);
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
					};
				}
				$("#dev_form").submit(function(e)
				{
					var keep = keep_multi_devs._config.value;
					var remove = remove_multi_devs._config.value;
					var tbl_name = "developers";
					var input = getInput(tbl_name, keep, remove);
					$.ajax(input);

					e.preventDefault();
				});
				$("#pub_form").submit(function(e)
				{
					var keep = keep_multi_pubs._config.value;
					var remove = remove_multi_pubs._config.value;
					var tbl_name = "publishers";
					var input = getInput(tbl_name, keep, remove);
					$.ajax(input);

					e.preventDefault();
				});
			<?php endif;?>
		});
	</script>
<?php });?>
<?= $Header->print(); ?>

		<form id="dev_form" class="row justify-content-center">
			<div class="col-6">
				<h2>Developer</h2>
				<div class="card-footer">
					<h4>Keep</h4>
					<p><span id="devs_list_keep"></span></p>
				</div>
				<div class="card-footer">
					<h4>Remove</h4>
					<p><span id="devs_list_remove"></span></p>
				</div>
				<p><button type="submit" class="btn btn-primary btn-block">Merge</button></p>
			</div>
		</form>
		<form id="pub_form" class="row justify-content-center">
			<div class="col-6">
				<h2>Publisher</h2>

				<div class="card-footer">
					<h4>Keep</h4>
					<p><span id="pubs_list_keep"></span></p>
				</div>
				<div class="card-footer">
					<h4>Remove</h4>
					<p><span id="pubs_list_remove"></span></p>
				</div>
				<p><button type="submit" class="btn btn-primary btn-block">Merge</button></p>
			</div>
		</form>
	</form>

<?php FOOTER::print(); ?>

