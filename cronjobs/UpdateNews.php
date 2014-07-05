#!/usr/bin/php
<?php
/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\cronjobs;

require (__DIR__ . '/../lib/AutoLoad.php');

use archportal\lib\Config;
use archportal\lib\CronJob;
use archportal\lib\Database;
use archportal\lib\Download;
use DateTime;
use PDO;
use RuntimeException;
use SimpleXMLElement;

spl_autoload_register('archportal\lib\AutoLoad::loadClass');
set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

class UpdateNews extends CronJob
{

    public function execute()
    {
        try {
            $newsEntries = $this->getNewsEntries();
            $this->updateNewsEntries($newsEntries);
        } catch (RuntimeException $e) {
            $this->printError('Warning: UpdateNews failed: ' . $e->getMessage());
        }
    }

    private function updateNewsEntries(SimpleXMLElement $newsEntries)
    {
        try {
            Database::beginTransaction();
            $stm = Database::prepare('
                INSERT INTO
                    news_feed
                SET
                    id = :id,
                    title = :title,
                    link = :link,
                    summary = :summary,
                    author_name = :author_name,
                    author_uri = :author_uri,
                    updated = :updated
                ON DUPLICATE KEY UPDATE
                    title = :title,
                    summary = :summary,
                    author_name = :author_name,
                    updated = :updated
            ');
            foreach ($newsEntries as $newsEntry) {
                $stm->bindParam('id', $newsEntry->id, PDO::PARAM_STR);
                $stm->bindParam('title', $newsEntry->title, PDO::PARAM_STR);
                $stm->bindParam('link', $newsEntry->link->attributes()->href, PDO::PARAM_STR);
                $stm->bindParam('summary', $newsEntry->summary, PDO::PARAM_STR);
                $stm->bindParam('author_name', $newsEntry->author->name, PDO::PARAM_STR);
                $stm->bindParam('author_uri', $newsEntry->author->uri, PDO::PARAM_STR);
                $stm->bindValue('updated', (new DateTime($newsEntry->updated))->getTimestamp(), PDO::PARAM_INT);
                $stm->execute();
            }
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            $this->printError('Warning: updateNews failed: ' . $e->getMessage());
        }
    }

    /**
     * @return \SimpleXMLElement
     */
    private function getNewsEntries()
    {
        $download = new Download(Config::get('news', 'feed'));
        $feed = new SimpleXMLElement($download->getFile(), 0, true);

        return $feed->entry;
    }

}

UpdateNews::run();
