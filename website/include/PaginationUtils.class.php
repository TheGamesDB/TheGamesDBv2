<?php

class PaginationUtils
{

	static function getPage()
	{
		if(!empty($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0)
		{
			return $_GET['page'];
		}
		return 1;
	}

	public static function Create($has_next_page, $custom_url = '')
	{
		$page = PaginationUtils::getPage();
		{
			$GET = $_GET;
			$GET['page'] = $page - 1;
			$previous_args = "?" . http_build_query($GET,'','&');

			$GET['page'] = $page + 1;
			$next_args = "?" . http_build_query($GET,'','&');
			?>
			<nav aria-label="Page navigation example">
				<ul class="pagination justify-content-center">
					<li class="page-item <?= $page <= 1 ? "disabled" : ""?>">
					<a class="page-link" href="<?= $page > 1 ? $custom_url . $previous_args : "#"?>" tabindex="-1">&lt;&lt; Previous</a>
					</li>
					<li class="page-item <?= !$has_next_page ? "disabled" : ""?>">
						<a class="page-link" href="<?= $has_next_page > 0 ? $custom_url . $next_args : "#"?>">Next &gt;&gt;</a>
					</li>
				</ul>
			</nav>
			<?php
		}
	}
}
