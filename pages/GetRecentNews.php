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

class GetRecentNews extends GetFile {

private $board 			= 20;
private $archNewsForum 		= 257;
private $importantTag		= 3;

public function prepare()
	{
	}

public function show()
	{
	if (!($content = $this->ObjectCache->getObject('AL:GetRecentNews:Atom:')))
		{
		$this->initDB();
		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					t.id,
					t.name,
					t.summary,
					p.dat,
					p.text,
					p.username,
					p.userid
				FROM
					current.threads t,
					current.posts p
				WHERE
					t.forumid = ?
					AND t.deleted = 0
					AND t.tag = ?
					AND p.threadid = t.id
					AND p.counter = 0
				ORDER BY
					t.id DESC
				LIMIT
					25
				');
			$stm->bindInteger($this->archNewsForum);
			$stm->bindInteger($this->importantTag);
			$threads = $stm->getRowSet();

			$lastdate = 0;
			$entries = '';

			foreach($threads as $thread)
				{
				if ($thread['dat'] > $lastdate)
					{
					$lastdate = $thread['dat'];
					}

				$entries .=
				'
				<entry>
					<id>http://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'</id>
					<title>'.$thread['name'].'</title>
					<link rel="alternate" type="text/html" href="http://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'" />
					<updated>'.date('c', $thread['dat']).'</updated>
					<summary>'.$thread['summary'].'</summary>
					<author>
						<name>'.$thread['username'].'</name>
						<uri>http://forum.archlinux.de/?page=ShowUser;id='.$this->board.';user='.$thread['userid'].'</uri>
					</author>
					<content type="html">'.htmlspecialchars($thread['text']).'</content>
				</entry>
				';
				}
			}
		catch (DBNoDataException $e)
			{
			}

		if (isset($stm))
			{
			$stm->close();
			}

		$content =
'<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="de">
	<id>http://forum.archlinux.de/?page=Threads;id='.$this->board.';forum='.$this->archNewsForum.'</id>
	<title>archlinux.de :: Aktuelle Ankündigungen</title>
	<link rel="self" type="application/atom+xml" href="http://forum.archlinux.de/?page=Threads;id='.$this->board.';forum='.$this->archNewsForum.'" />
	<link rel="alternate" type="text/html" href="http://www.archlinux.de/" />
	<updated>'.date('c', $lastdate).'</updated>
	'.$entries.'
</feed>';

		$this->ObjectCache->addObject('AL:GetRecentNews:Atom:', $content, 60*60);
		}

	$this->sendInlineFile('application/atom+xml; charset=UTF-8', 'recent.xml', strlen($content), $content);
	}

}

?>
