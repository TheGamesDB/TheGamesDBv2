<?php

include __DIR__ . "/../vendor/autoload.php";

use claviska\SimpleImage;

$_size = array(
	"small" => 150,
	"thumb" => 300,
	"cropped_center_thumb" => 300,
	"medium" => 720,
	"large" => 1080,
);

$_compression = array(
	"small" => 60,
	"thumb" => 75,
	"cropped_center_thumb" => 75,
	"medium" => 85,
	"large" => 90,
);

if(isset($_SERVER['REDIRECT_URL']))
{
	$PATHs = explode("/", $_SERVER['REDIRECT_URL']);
	$size = $PATHs[2];
	array_splice($PATHs, 1, 2);
	$original_image = __DIR__ . "/images/original" . implode("/", $PATHs);
	$dest_image = __DIR__ . "/images/$size" . implode("/", $PATHs);
	if(file_exists($original_image) && !file_exists($dest_image))
	{
		try
		{
			$image = new SimpleImage();
		
			$image = $image->fromFile($original_image);
			if($size == "cropped_center_thumb")
			{
				if($image->getHeight() > $image->getWidth())
				{
					$image = $image->thumbnail($_size[$size], 533, 'center');
				}
				else
				{
					$image = $image->thumbnail(533, $_size[$size], 'center');
				}
			}
			else
			{
				if($image->getHeight() > $image->getWidth())
				{
					$image = $image->resize($_size[$size], null);
				}
				else
				{
					$image = $image->resize(null, $_size[$size]);
				}
			}

			if(!file_exists(dirname($dest_image)))
			{
				mkdir(dirname($dest_image), 0755, true);
			}

			$image->toFile($dest_image, 'image/jpeg', $_compression[$size]);
			$image->toScreen('image/jpeg');
			return;
		}
		catch(Exception $err)
		{
			echo $err->getMessage() . "<br>";
		}
	}
}

header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
echo "File Not Found";

?>
