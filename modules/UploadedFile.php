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

class UploadedFile extends File {

private $file = array();

public function __construct($name)
	{
	if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name']))
		{
		$this->file = $_FILES[$name];

		try
			{
			$this->file['type'] = $this->getTypeFromFile($this->file['tmp_name']);
			}
		catch (FileException $e)
			{
			// we will use the type provides by the client
			}

		if (!$this->isAllowedType($this->file['type']))
			{
			throw new FileException(sprintf($this->L10n->getText('Uploading files of type %s is not allowed'), htmlspecialchars($this->file['type'])));
			}

		if ($this->getFileSize() >= $this->Settings->getValue('file_size'))
			{
			throw new FileException(sprintf($this->L10n->getText('File is larger than %s Bytes'), $this->Settings->getValue('file_size')));
			}
		}
	elseif (isset($_FILES[$name]) && !empty($_FILES[$name]['error']))// && !empty($_FILES[$name]['name']))
		{
		switch ($_FILES[$name]['error'])
			{
			case 1 : $message = sprintf($this->L10n->getText('File is larger than %s Bytes'), ini_get('upload_max_filesize')); break;
			case 2 : $message = $this->L10n->getText('The uploaded file exceeds the directive that was specified in the form'); break;
			case 3 : $message = $this->L10n->getText('The uploaded file was only partially uploaded'); break;
			case 4 : $message = $this->L10n->getText('No file was uploaded'); break;
			case 6 : $message = $this->L10n->getText('Missing a temporary folder'); break;
			case 7 : $message = $this->L10n->getText('Failed to write file to disk'); break;
			case 8 : $message = $this->L10n->getText('File upload stopped by extension'); break;
			default : $message = $this->L10n->getText('Unknown error. Code: ').$_FILES[$name]['error'];
			}

		if ($_FILES[$name]['error'] == 4)
			{
			throw new FileNotUploadedException($message);
			}
		else
			{
			throw new FileException($this->L10n->getText('No file was uploaded').' - '.$message);
			}
		}
	else
		{
		throw new FileNotUploadedException($this->L10n->getText('No file was uploaded'));
		}
	}

public function __destruct()
	{
	if (isset($this->file['tmp_name']) && file_exists($this->file['tmp_name']))
		{
		unlink($this->file['tmp_name']);
		}
	}

public function getFileName()
	{
	return $this->file['name'];
	}

public function getFileSize()
	{
	return $this->file['size'];
	}

public function getFileType()
	{
	return $this->file['type'];
	}

public function getFileContent()
	{
	return file_get_contents($this->file['tmp_name']);
	}

private function getTypeFromFile($file)
	{
	$finfo = finfo_open(FILEINFO_MIME);
	$type = finfo_file($finfo, $file);
	finfo_close($finfo);
	/** @TODO: review with php 5.3 */
	// new version produces strings like 'image/png; charset=binary'
	// we only need the first part
	$type = strtok($type, ';');

	return $type;
	}

}

class FileNotUploadedException extends FileException{}

?>
