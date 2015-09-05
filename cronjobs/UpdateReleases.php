#!/usr/bin/env php
<?php
/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

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

require(__DIR__ . '/../vendor/autoload.php');

use archportal\lib\Config;
use archportal\lib\CronJob;
use archportal\lib\Database;
use archportal\lib\Download;

set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

class UpdateReleases extends CronJob
{

    public function execute()
    {
        try {
            $releng = $this->getRelengReleases();
            if ($releng['version'] != 1) {
                throw new RuntimeException('incompatible releng/releases version');
            }
            $releases = $releng['releases'];
            if (empty($releases)) {
                throw new RuntimeException('there are no releases');
            }
            $this->updateRelengReleases($releases);
        } catch (RuntimeException $e) {
            $this->printError('Warning: UpdateReleases failed: ' . $e->getMessage());
        }
    }

    private function updateRelengReleases($releases)
    {
        try {
            Database::beginTransaction();
            Database::query('DELETE FROM releng_releases');
            $stm = Database::prepare('
                INSERT INTO
                    releng_releases
                SET
                    version = :version,
                    available = :available,
                    info = :info,
                    iso_url = :iso_url,
                    md5_sum = :md5_sum,
                    created = :created,
                    kernel_version = :kernel_version,
                    release_date = :release_date,
                    torrent_url = :torrent_url,
                    sha1_sum = :sha1_sum,
                    torrent_comment = :torrent_comment,
                    torrent_info_hash = :torrent_info_hash,
                    torrent_piece_length = :torrent_piece_length,
                    torrent_file_name = :torrent_file_name,
                    torrent_announce = :torrent_announce,
                    torrent_file_length = :torrent_file_length,
                    torrent_piece_count = :torrent_piece_count,
                    torrent_created_by = :torrent_created_by,
                    torrent_creation_date = :torrent_creation_date,
                    magnet_uri = :magnet_uri
            ');
            foreach ($releases as $release) {
                $stm->bindParam('version', $release['version'], PDO::PARAM_STR);
                $stm->bindValue('available', $release['available'] ? 1 : 0, PDO::PARAM_INT);
                $stm->bindParam('info', $release['info'], PDO::PARAM_STR);
                $stm->bindParam('iso_url', $release['iso_url'], PDO::PARAM_STR);
                $stm->bindParam('md5_sum', $release['md5_sum'], PDO::PARAM_STR);
                $stm->bindValue('created', $this->getTimestamp($release['created']), PDO::PARAM_INT);
                $stm->bindParam('kernel_version', $release['kernel_version'], PDO::PARAM_STR);
                $stm->bindParam('release_date', $release['release_date'], PDO::PARAM_STR);
                $stm->bindParam('torrent_url', $release['torrent_url'], PDO::PARAM_STR);
                $stm->bindParam('sha1_sum', $release['sha1_sum'], PDO::PARAM_STR);
                $stm->bindValue('torrent_comment', isset($release['torrent']['comment']) ? $release['torrent']['comment'] : null, PDO::PARAM_STR);
                $stm->bindValue('torrent_info_hash', isset($release['torrent']['info_hash']) ? $release['torrent']['info_hash'] : null, PDO::PARAM_STR);
                $stm->bindValue('torrent_piece_length', isset($release['torrent']['piece_length']) ? $release['torrent']['piece_length'] : null, PDO::PARAM_INT);
                $stm->bindValue('torrent_file_name', isset($release['torrent']['file_name']) ? $release['torrent']['file_name'] : null, PDO::PARAM_STR);
                $stm->bindValue('torrent_announce', isset($release['torrent']['announce']) ? $release['torrent']['announce'] : null, PDO::PARAM_STR);
                $stm->bindValue('torrent_file_length', isset($release['torrent']['file_length']) ? $release['torrent']['file_length'] : null, PDO::PARAM_INT);
                $stm->bindValue('torrent_piece_count', isset($release['torrent']['piece_count']) ? $release['torrent']['piece_count'] : null, PDO::PARAM_INT);
                $stm->bindValue('torrent_created_by', isset($release['torrent']['created_by']) ? $release['torrent']['created_by'] : null, PDO::PARAM_STR);
                $stm->bindValue('torrent_creation_date', isset($release['torrent']['creation_date']) ? $this->getTimestamp($release['torrent']['creation_date']) : null, PDO::PARAM_INT);
                $stm->bindParam('magnet_uri', $release['magnet_uri'], PDO::PARAM_STR);
                $stm->execute();
            }
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            $this->printError('Warning: UpdateReleases failed: ' . $e->getMessage());
        }
    }

    private function getTimestamp($data)
    {
        if (is_null($data)) {
            return null;
        } else {
            return (new DateTime($data))->getTimestamp();
        }
    }

    private function getRelengReleases()
    {
        $download = new Download(Config::get('releng', 'releases'));

        $content = file_get_contents($download->getFile());
        if (empty($content)) {
            throw new RuntimeException('empty releng releases', 1);
        }
        $releng = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException('could not decode releng releases', 1);
        }

        return $releng;
    }

}

UpdateReleases::run();
