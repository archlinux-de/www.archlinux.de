<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

class GetRecentNews extends GetFile {

private $board 			= 20;
private $archNewsForum 		= 257;


public function show()
	{
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
				ll.threads t,
				ll.posts p
			WHERE
				t.forumid = ?
				AND t.deleted = 0
				AND p.threadid = t.id
				AND p.counter = 0
			ORDER BY
				t.id DESC
			LIMIT
				25
			');
		$stm->bindInteger($this->archNewsForum);
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
				<id>https://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'</id>
				<title>'.$thread['name'].'</title>
				<link rel="alternate" type="text/html" href="https://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'" />
				<updated>'.date('c', $thread['dat']).'</updated>
				<summary>'.$thread['summary'].'</summary>
				<author>
					<name>'.$thread['username'].'</name>
					<uri>https://forum.archlinux.de/?page=ShowUser;id='.$this->board.';user='.$thread['userid'].'</uri>
				</author>
				<content type="html">'.htmlspecialchars($thread['text']).'</content>
			</entry>
			';
			}
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}

	$content =
'<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="de">
	<id>https://forum.archlinux.de/?page=Threads;id='.$this->board.';forum='.$this->archNewsForum.'</id>
	<title>archlinux.de :: Aktuelle Ankündigungen</title>
	<link rel="self" type="application/atom+xml" href="https://forum.archlinux.de/?page=Threads;id='.$this->board.';forum='.$this->archNewsForum.'" />
	<link rel="alternate" type="text/html" href="https://www.archlinux.de/" />
	<updated>'.date('c', $lastdate).'</updated>
	'.$entries.'
</feed>';

	$this->sendInlineFile('application/atom+xml; charset=UTF-8', 'news.xml', $content);
	}

}

?>
