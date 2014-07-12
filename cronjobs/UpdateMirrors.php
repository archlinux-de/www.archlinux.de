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

require (__DIR__ . '/../vendor/autoload.php');

use archportal\lib\Config;
use archportal\lib\CronJob;
use archportal\lib\Database;
use archportal\lib\Download;
use DateTime;
use PDO;
use RuntimeException;

set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

class UpdateMirrors extends CronJob
{

    public function execute()
    {
        try {
            $status = $this->getMirrorStatus();
            if ($status['version'] != 3) {
                throw new RuntimeException('incompatible mirrorstatus version');
            }
            $mirrors = $status['urls'];
            if (empty($mirrors)) {
                throw new RuntimeException('mirrorlist is empty');
            }
            $this->updateMirrorlist($mirrors);
        } catch (RuntimeException $e) {
            $this->printError('Warning: UpdateMirrors failed: ' . $e->getMessage());
        }
    }

    private function updateMirrorlist($mirrors)
    {
        try {
            Database::beginTransaction();
            Database::query('DELETE FROM mirrors');
            $stm = Database::prepare('
            INSERT INTO
                mirrors
            SET
                url = :url,
                protocol = :protocol,
                countryCode = :countryCode,
                lastsync = :lastsync,
                delay = :delay,
                durationAvg = :durationAvg,
                score = :score,
                completionPct = :completionPct,
                durationStddev = :durationStddev
            ');
            foreach ($mirrors as $mirror) {
                $stm->bindParam('url', $mirror['url'], PDO::PARAM_STR);
                $stm->bindParam('protocol', $mirror['protocol'], PDO::PARAM_STR);
                $stm->bindParam('countryCode', $mirror['country_code'], PDO::PARAM_STR);
                if (is_null($mirror['last_sync'])) {
                    $lastSync = null;
                } else {
                    $lastSyncDate = new DateTime($mirror['last_sync']);
                    $lastSync = $lastSyncDate->getTimestamp();
                }
                $stm->bindParam('lastsync', $lastSync, PDO::PARAM_INT);
                $stm->bindParam('delay', $mirror['delay'], PDO::PARAM_INT);
                $stm->bindParam('durationAvg', $mirror['duration_avg'], PDO::PARAM_STR);
                $stm->bindParam('score', $mirror['score'], PDO::PARAM_STR);
                $stm->bindParam('completionPct', $mirror['completion_pct'], PDO::PARAM_STR);
                $stm->bindParam('durationStddev', $mirror['duration_stddev'], PDO::PARAM_STR);
                $stm->execute();
            }
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            $this->printError('Warning: updateMirrorlist failed: ' . $e->getMessage());
        }
    }

    private function getMirrorStatus()
    {
        $download = new Download(Config::get('mirrors', 'status'));

        $content = file_get_contents($download->getFile());
        if (empty($content)) {
            throw new RuntimeException('empty mirrorstatus', 1);
        }
        $mirrors = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException('could not decode mirrorstatus', 1);
        }

        return $mirrors;
    }

}

UpdateMirrors::run();
