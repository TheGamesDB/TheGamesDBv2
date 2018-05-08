<?php
require_once __DIR__ . "/header.footer.class.php";

class ErrorPage
{

	public function __construct()
	{
	}
	static $HEADER_OOPS_ERROR = "OOPS!! An Error Has Occured";
	static $HEADER_UNEXPECTED_ERROR = "An Unexpected Error Has Occured";
	static $MSG_MISSING_PARAM_ERROR = "Parameters for this page was not provided.";
	static $MSG_INVALID_PARAM_ERROR = "Invalid Parameters were provided.";
	static $MSG_PLEASE_GO_BACK_OR_TRY_AGAIN_ERROR = "Please go back or try again";
	static $MSG_REMOVED_GAME_INVALID_PARAM_ERROR = "The game you're looking for does not exist or has been removed.";
	static $MSG_NOT_LOGGED_IN_EDIT_ERROR = "Please login to be able to edit this page.";
	static $MSG_NO_PERMISSION_TO_EDIT_ERROR = "You dont currently have permission to edit this page.";

	private $_error_header = "Error Has Occured";
	private $_error_msg = "Please go back or try again";

	public function SetHeader($error_header)
	{
		$this->_error_header = $error_header;
	}

	public function SetMSG($error_msg)
	{
		$this->_error_msg = $error_msg;
	}

	public function print_die()
	{
		$Header = new HEADER();
		$Header->setTitle("TGDB - Error");
		?>
		<?= $Header->print(); ?>
			<div class="container-fluid">


				<div class="row justify-content-center" style="margin:10px;">
					<div class="col-12 col-md-10">
							<div class="card">
								<div class="card-header">
									<legend><?= $this->_error_header ?></legend>
								</div>
								<div class="card-body">
									<p><?= $this->_error_msg ?></p>
								</div>
							</div>

					</div>
				</div>
			</div>

<?php
		FOOTER::print();
		die();
	}
}
