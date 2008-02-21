<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/

require (LL_PATH.'modules/ObjectCache.php');
Modul::__set('ObjectCache', new ObjectCache());

class GetFileFromMirror extends Page{

public function prepare()
	{
	$this->setValue('title', 'Lade Datei von einem Spiegel');

	try
		{
		$file = $this->Io->getString('file');
		}
	catch (IoRequestException $e)
		{
		$this->showFailure('keine Datei angegeben!');
		}

	if (count($this->Settings->getValue('mirrors')) == 0)
		{
		$this->showFailure('keine Spiegel gefunden!');
		}

	$mirror = $this->getRandomMirror($file);

	if (!($url = $this->ObjectCache->getObject('AL:GetFileFromMirror::'.$mirror.':'.md5($file))))
		{
		$url = $mirror.$file;

		try
			{
			$size = $this->getRemoteFileSize($url);
			if (empty($size) || $size < 1)
				{
				throw new IoException('Dateigröße ist: '.$size);
				}
			}
		catch (Exception $e)
			{
			$this->ObjectCache->addObject('AL:GetFileFromMirror:BlackList:'.$mirror.':'.md5($file), 'e', 60*60);

			$this->showFailure('Fehler beim Laden der Datei:<br /><code>'.$file.'</code><br />von<br /><strong>'.$mirror.'</strong>.<p><strong>'.$e->getMessage().'</strong></p><p>Alternative Server:'.$this->getAlternateMirrorList($url, $file).'</p>');
			}

		$this->ObjectCache->addObject('AL:GetFileFromMirror::'.$mirror.':'.md5($file), $url, 60*60);
		}
print_r($size);exit;
	$this->setValue('body', $size);
// 	$this->Io->redirectToUrl($url);
	}

private function getAlternateMirrorList($url, $file)
	{
	$list = '<ul>';

	foreach (array_keys($this->Settings->getValue('mirrors')) as $mirror)
		{
		if ($mirror.$file == $url || $this->ObjectCache->getObject('AL:GetFileFromMirror:BlackList:'.$mirror.':'.md5($file)) != false)
			{
			continue;
			}
		$list .= '<li><a href="'.$mirror.$file.'">'.$mirror.'</a></li>';
		}

	return $list.'</ul>';
	}

private function getRandomMirror($file)
	{
	$tempMirrors = array();

	foreach ($this->Settings->getValue('mirrors') as $mirror => $probability)
		{
		if ($this->ObjectCache->getObject('AL:GetFileFromMirror:BlackList:'.$mirror.':'.md5($file)) == false)
			{
			for ($i = 0; $i < $probability; $i++)
				{
				$tempMirrors[] = $mirror;
				}
			}
		}

	$randomIndex = array_rand($tempMirrors);

	return $tempMirrors[$randomIndex];
	}

private function curlInit($url)
	{
	if (!preg_match('/^(https?|ftp):\/\//', $url))
		{
 		throw new IoException('Nur http und ftp-Protokolle erlaubt');
		}

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	curl_setopt($curl, CURLOPT_ENCODING, '');
	curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:support@laber-land.de');

	return $curl;
	}

public function getRemoteFileSize($url)
	{
	$curl = $this->curlInit($url);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	 curl_exec($curl);
	 $error = curl_getinfo($curl);
// 	$size = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
// 	$error = curl_errno($curl);
	curl_close($curl);

	return $error;
	}

}

?>