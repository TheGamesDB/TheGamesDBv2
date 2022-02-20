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
$Header->setTitle("TGDB - Add Platforms");
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

		function createLinkElement(name, id)
		{
			var a = document.createElement('a');
			var linkText = document.createTextNode(name);
			a.appendChild(linkText);
			a.title = name;
			a.href = "/platform.php?id=" + id;
			a.target = "_blank";
			return a;
		}

		function createListElement(link)
		{
			var node = document.createElement("li");
			node.appendChild(link);
			return node;
		}

		$(document).ready(function()
		{
			<?php if($_user->hasPermission('m_delete_games')) : ?>

				$("#platform_form").submit(function(e)
				{
					var formData = new FormData($("#platform_form")[0]);
					$.ajax({
						type: "POST",
						url: "./actions/add_platform.php",
						data: formData,
						contentType: false,
						processData: false,
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
								if(obj.code == 0)
								{
									alert("Success")
									document.getElementById("platform_form").reset(); 
									list = createListElement(createLinkElement(obj.name, obj.id))
									document.getElementById("added").appendChild(list);
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
								alert(data)
								return;
							}
						}
					});
					e.preventDefault();
				});
			<?php endif;?>
		});
	</script>
<?php });?>
<?= $Header->print(); ?>

		<form id="platform_form" method="POST" action="/actions/add_platform.php" enctype="multipart/form-data" class="row justify-content-center">
			<div class="col-6">
				<h2>Platform</h2>
				<div class="form-group">
					<label>Name</label>
					<input name="name" class="form-control" />
				</div>
				<div class="form-group">
					<label>Manufacturer</label>
					<input name="manufacturer" class="form-control" />
				</div>
				<div class="form-group">
					<label>Developer</label>
					<input name="developer" class="form-control" />
				</div>
				<div class="form-group">
					<label>Media Medium</label>
					<input name="media" class="form-control" />
				</div>
				<div class="form-group">
					<label>CPU</label>
					<input name="cpu" class="form-control" />
				</div>
				<div class="form-group">
					<label>Memory</label>
					<input name="memory" class="form-control" />
				</div>
				<div class="form-group">
					<label>Graphics</label>
					<input name="graphics" class="form-control" />
				</div>
				<div class="form-group">
					<label>Sound</label>
					<input name="sound" class="form-control" />
				</div>
				<div class="form-group">
					<label>Display</label>
					<input name="display" class="form-control" />
				</div>
				<div class="form-group">
					<label>Max Controllers</label>
					<input name="maxcontrollers" type="number" class="form-control"/>
				</div>

				<div class="form-group">
					<label>Trailer</label>
					<input name="youtube" class="form-control" />
				</div>
				
				<div class="form-group">
					<label>Overview</label>
					<textarea name="overview" class="form-control" row="4" > </textarea>
				</div>

				<div class="form-group">
					<label>Icon 48x48</label>
					<input type="file" name="icon" id="form-control">
				</div>
				<div class="form-group">
					<label>Boxart</label>
					<input type="file" name="boxart" id="form-control">
				</div>

				<p><button type="submit" class="btn btn-primary btn-block">Add</button></p>
				<div class="alert alert-success">
					<ul class="" id="added">
					</ul>
				</div>

			</div>
		</form>
	</form>

<?php FOOTER::print(); ?>

