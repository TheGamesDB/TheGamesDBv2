<?php

class WebUtils
{
	static function truncate($text, $maxlen, $add_leading_trail = false)
	{
		if (strlen($text) < $maxlen)
		{
			return $text;
		}
		$pos = strpos($text, " ", $maxlen - 5);
		if ($pos !== false && !($add_leading_trail && $pos + 3 >= strlen($text)))
		{
			$ret = substr($text, 0, $pos);
			if ($add_leading_trail)
			{
				$ret .= "...";
			}
			return $ret;
		}
		return $text;
	}
}

?>