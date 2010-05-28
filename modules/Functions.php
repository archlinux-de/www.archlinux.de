<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/

function hexVal($in)
	{
	$result = preg_replace('/[^0-9a-fA-F]/', '', $in);
	return (empty($result) ? 0 : $result);
	}

function nat($number)
	{
	return ($number < 0 ? 0 : floor($number));
	}

function unhtmlspecialchars($string)
	{
	return htmlspecialchars_decode($string, ENT_COMPAT);
	}

// see http://w3.org/International/questions/qa-forms-utf-8.html
function is_unicode($input)
	{
	# long values will make pcre segfaulting...
	for ($i = 0; $i <= mb_strlen($input, 'UTF-8'); $i += 1000)
		{
		if (!preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]			# ASCII
			| [\xC2-\xDF][\x80-\xBF]		# non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
			)*$%xs', mb_substr($input, $i, 1000, 'UTF-8')))
			{
			return false;
			}
		}

	return true;
	}

function cutString($string, $length)
	{
	// Verhindere das Abschneiden im Entity
	$string = unhtmlspecialchars(trim($string));
	$string =  (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length-3), 'UTF-8').'...' : $string);
	return htmlspecialchars($string);
	}

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
	# ignore recoverable errors
	restore_error_handler();
	$temp = error_reporting(0);
	$src = imagecreatefromstring($image);
	error_reporting($temp);
	set_error_handler('ErrorHandler');
	
	if ($src === false)
		{
		throw new Exception('wrong format');		
		}

	$width = imagesx($src);
	$height = imagesy($src);

	if ($width <= $size && $height <= $size)
		{
		/** TODO: besser eigene Exception */
		throw new RuntimeException('we do not need to resize');
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

	if (strpos($type, 'image/jpeg') === 0)
		{
		imagejpeg($img, '', 80);
		}
	elseif (strpos($type, 'image/png') === 0)
		{
		imagepng($img);
		}
	elseif (strpos($type, 'image/gif') === 0)
		{
		imagegif($img);
		}
	else
		{
		throw new Exception('unknown image-type');
		}

	$thumb = ob_get_contents();
	ob_end_clean();

	imagedestroy($img);

	return $thumb;
	}

?>