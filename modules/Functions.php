<?php


function hexVal($in)
	{
	$result = preg_replace('/[^0-9a-fA-F]/', '', $in);
	return (empty($result) ? 0 : $result);
	}

function mTime()
	{
	$ms = explode(' ', microtime());
	return ((float) $ms[0] + (float) $ms[1]);
	}

/**
* Wandle $number in natrliche Zahl (inkl. 0) um
*/
function nat($number)
	{
	return ($number < 0 ? 0 : floor($number));
	}

function unhtmlspecialchars($string)
	{
	$string = str_replace ( '&amp;', '&', $string );
	$string = str_replace ( '&quot;', '"', $string );
	$string = str_replace ( '&lt;', '<', $string );
	$string = str_replace ( '&gt;', '>', $string );

	return $string;
	}

/**
* gibt die Tageszeit aus
* @param &$time Unix-Zeitstempel
*/
function formatDate($time)
	{
	if (empty($time))
		{
		return '';
		}

	$return = date('j.n.Y \u\m G:i', $time);

	if (date('zY',$time) == date('zY'))
		{
		$return = '<strong>'.$return.'</strong>';
		}

	return $return;
	}

function cutString($string, $length)
	{
	// Verhindere das Abschneiden im Entity
	$string = unhtmlspecialchars($string);
	$string =  (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length-3), 'UTF-8').'...' : $string);
	return htmlspecialchars($string);
	}
/*
function gzdecode($string)
	{
	return gzinflate(substr($string, 10));
	}*/

function generatePassword($length = 8)
	{
	$chars = array(
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

	$password = '';

	for ($i = 0; $i < $length; $i++)
		{
		$password .= $chars[rand(0, count($chars)-1)];
		}

	return $password;
	}
/** @TODO: Sollte in eigene Klasse */
function resizeImage($image, $type, $size)
	{
	try
		{
		$src = imagecreatefromstring($image);
		}
	catch (Exception $e)
		{
		throw new Exception('wrong format');
		}

	$width = imagesx($src);
	$height = imagesy($src);

	if ($width <= $size && $height <= $size)
		{
		/** TODO: besser eigene Exception */
		throw new Exception('we do not need to resize');
		}
	else
		{
		if ($width >= $height)
			{
			$new_w = $size;
			$new_h = abs($new_w * ($height/$width));
			}
		else
			{
			$new_h = $size;
			$new_w = abs($new_h * ($width/$height));
			}
		}

	$img = imagecreatetruecolor($new_w,$new_h);

	if     ($type == 'image/png')
		{
		imagealphablending($img, false);
		imagesavealpha($img, true);
		}
	elseif ($type == 'image/gif')
		{
		imagealphablending($img, true);
		}

	imagecopyresampled($img,$src,0,0,0,0,$new_w,$new_h,$width,$height);

	ob_start();

	switch ($type)
		{
		case 'image/jpeg' 	: imagejpeg($img, '', 80); 	break;
		case 'image/png' 	: imagepng($img); 		break;
		case 'image/gif' 	: imagegif($img); 		break;
		default 		: throw new Exception('unknown image-type');
		}

	$thumb = ob_get_contents();
	ob_end_clean();

	imagedestroy($img);

	return $thumb;
	}

function getTypeFromContent($content)
	{
	$finfo = finfo_open(FILEINFO_MIME);
	$type = finfo_buffer($finfo, $content);
	finfo_close($finfo);
	
	return $type;
	}

function getTypeFromFile($file)
	{
	$finfo = finfo_open(FILEINFO_MIME);
	$type = finfo_file($finfo, $file);
	finfo_close($finfo);
	
	return $type;
	}

?>
