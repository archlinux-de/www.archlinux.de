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

class GetOpenSearch extends GetFile{

public function prepare()
	{
	$this->exitIfCached();
	$this->initDB();
	}

public function show()
	{
	$xml = '<?xml version="1.0"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>archlinux.de :: Paketsuche</ShortName>
<Description>Suche nach Paketen für Arch Linux</Description>
<Image height="16" width="16" type="image/x-icon">http://www.archlinux.de/favicon.ico</Image>
<Url type="text/html" method="get" template="http://www.archlinux.de/?page=Packages;submit=;search={searchTerms}"/>
</OpenSearchDescription>';

	$this->Io->setContentType('Content-Type: application/opensearchdescription+xml; charset=UTF-8');
	$this->Io->out($xml);
	}

}

?>