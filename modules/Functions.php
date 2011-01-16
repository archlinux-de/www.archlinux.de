<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

function hexVal($in) {
	$result = preg_replace('/[^0-9a-fA-F]/', '', $in);
	return (empty($result) ? 0 : $result);
}

function nat($number) {
	return ($number < 0 ? 0 : floor($number));
}

function unhtmlspecialchars($string) {
	return htmlspecialchars_decode($string, ENT_COMPAT);
}

// see http://w3.org/International/questions/qa-forms-utf-8.html
function is_unicode($input) {
	# long values will make pcre segfaulting...
	for ($i = 0;$i <= mb_strlen($input, 'UTF-8');$i+= 1000) {
		if (!preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]			# ASCII
			| [\xC2-\xDF][\x80-\xBF]		# non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
			)*$%xs', mb_substr($input, $i, 1000, 'UTF-8'))) {
			return false;
		}
	}
	return true;
}

function cutString($string, $length) {
	// Verhindere das Abschneiden im Entity
	$string = unhtmlspecialchars(trim($string));
	$string = (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length - 3) , 'UTF-8') . '...' : $string);
	return htmlspecialchars($string);
}

?>
