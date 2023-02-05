<?php

require __DIR__ . '/../vendor/autoload.php';

use TheGamesDB\TGDB;
use TheGamesDB\Header;
use TheGamesDB\Footer;
use TheGamesDB\ErrorPage;

global $_user;

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

$API = TGDB::getInstance();
$devs_list = $API->GetDevsList();
$pubs_list = $API->GetPubsList();

$Header = new Header();
$Header->setTitle("TGDB - Add Dev/Pub");
$Header->appendRawHeader(function() { global $_user, $devs_list, $pubs_list; ?>

	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>

	<script type="text/javascript" src="/js/jquery.fancybox.3.3.5.js"></script>
	<script type="text/javascript" src="/js/fancybox.config.js"></script>

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
			<?php if($_user->hasPermission('m_delete_games')) : ?>
				function getRequest(form, arg_name)
				{
					return {
						type: "POST",
						url: "./actions/add_dev_pub.php",
						data: $(form).serialize(),
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
								if(obj.code == 0)
								{
									alert("Success")
									location.href = "/list_games.php?" + arg_name + "=" + obj.id;
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
					var input = getRequest(e.target, "dev_id");
					$.ajax(input);

					e.preventDefault();
				});
				$("#pub_form").submit(function(e)
				{
					var input = getRequest(e.target, "pub_id");
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
					<input name="name" type="text" class="w-100" />
					<input name="tbl"  value="dev" type="hidden" />
				</div>
				<p><button type="submit" class="btn btn-primary btn-block">Add</button></p>
			</div>
		</form>
		<form id="pub_form" class="row justify-content-center">
			<div class="col-6">
				<h2>Publisher</h2>

				<div class="card-footer">
					<input name="name" type="text" class="w-100" />
					<input name="tbl"  value="pub" type="hidden" />
				</div>
				<p><button type="submit" class="btn btn-primary btn-block">Add</button></p>
			</div>
		</form>
	</form>

<?php Footer::print(); ?>

