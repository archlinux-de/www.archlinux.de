<?php

declare (strict_types = 1);

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

namespace archportal\lib;

use RuntimeException;

abstract class CronJob
{
    private $lockName = 'cronjob';
    private $waitForLock = 600;
    private $quiet = false;

    public static function run()
    {
        $class = get_called_class();
        /** @var CronJob $instance */
        $instance = new $class();
        $instance->execute();
    }

    abstract public function execute();

    public function __construct()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', -1);

        if (count(getopt('q', array('quiet'))) > 0) {
            $this->quiet = true;
        }
        $this->aquireLock();
    }

    public function __destruct()
    {
        $this->releaseLock();
    }

    private function aquireLock()
    {
        if (!Database::aquireLock($this->lockName, $this->waitForLock)) {
            throw new RuntimeException('Another cron job is still running');
        }
    }

    private function releaseLock()
    {
        Database::releaseLock($this->lockName);
    }

    /**
     * @param string $text
     */
    protected function printDebug($text)
    {
        if (!$this->quiet) {
            echo $text, "\n";
        }
    }

    /**
     * @param string $text
     */
    protected function printError($text)
    {
        file_put_contents('php://stderr', $text."\n");
    }

    /**
     * @param int           $current
     * @param int           $total
     * @param string string $prefix
     */
    protected function printProgress($current, $total, $prefix = '')
    {
        if (!$this->quiet) {
            echo "\r", $prefix, round($current / $total * 100), '%';
            if ($current == $total) {
                echo "\n";
            }
        }
    }
}
