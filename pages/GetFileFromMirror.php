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

	$mirror = $this->getRandomMirror();
	$url = $mirror.$file;

	try
		{
		if ($this->Io->getRemoteFileSize($url) == 0)
			{
			throw new IoException('Datei ist leer: '.$url);
			}
		}
	catch (Exception $e)
		{
		$this->showFailure('Fehler beim Laden der Datei:<br /><code>'.$file.'</code><br />von<br /><strong>'.$mirror.'</strong>.<p>Alternativen Server:'.$this->getAlternateMirrorList($url, $file).'</p>');
		}

	$this->Io->redirectToUrl($url);
	}

private function getAlternateMirrorList($url, $file)
	{
	$list = '<ul>';

	foreach (array_keys($this->Settings->getValue('mirrors')) as $mirror)
		{
		if ($mirror.$file == $url)
			{
			continue;
			}
		$list .= '<li><a href="'.$mirror.$file.'">'.$mirror.'</a></li>';
		}

	return $list.'</ul>';
	}

private function getRandomMirror()
	{
	$tempMirrors = array();

	foreach ($this->Settings->getValue('mirrors') as $mirror => $probability)
		{
		for ($i = 0; $i < $probability; $i++)
			{
			$tempMirrors[] = $mirror;
			}
		}

	$randomIndex = array_rand($tempMirrors);

	return $tempMirrors[$randomIndex];
	}

}

?>