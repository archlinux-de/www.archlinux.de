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

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Database;
use archportal\lib\Input;
use archportal\lib\Output;
use PDO;

class GetFileFromMirror extends Output
{
    /** @var int */
    private $lastsync = 0;
    /** @var string */
    private $file = '';

    public function prepare()
    {
        $this->disallowCaching();
        $this->setContentType('text/plain; charset=UTF-8');
        $this->file = Input::get()->getString('file', '');
        if (!preg_match('#^[a-zA-Z0-9\.\-\+_/:]{1,255}$#', $this->file)) {
            $this->setStatus(Output::BAD_REQUEST);
            $this->showFailure('Invalid file name');
        }
        if (strpos($this->file, '/') === 0) {
            $this->file = substr($this->file, 1);
        }
        $repositories = implode('|', array_keys(Config::get('packages', 'repositories')));
        $architectures = implode('|', $this->getAvailableArchitectures());
        $pkgextension = '(?:'.$architectures.'|any).pkg.tar.(?:g|x)z';
        if (preg_match('#^('.$repositories.')/os/('.$architectures.')/([^-]+.*)-[^-]+-[^-]+-'.$pkgextension.'$#',
            $this->file, $matches)) {
            $pkgdate = Database::prepare('
                SELECT
                    packages.mtime
                FROM
                    packages
                    LEFT JOIN repositories
                    ON packages.repository = repositories.id
                    LEFT JOIN architectures
                    ON repositories.arch = architectures.id
                WHERE
                    packages.name = :pkgname
                    AND repositories.name = :repository
                    AND architectures.name = :architecture
                ');
            $pkgdate->bindParam('pkgname', $matches[3], PDO::PARAM_STR);
            $pkgdate->bindParam('repository', $matches[1], PDO::PARAM_STR);
            $pkgdate->bindParam('architecture', $matches[2], PDO::PARAM_STR);
            $pkgdate->execute();
            if ($pkgdate->rowCount() == 0) {
                $this->setStatus(Output::NOT_FOUND);
                $this->showFailure('Package was not found');
            }
            $this->lastsync = $pkgdate->fetchColumn();
        } elseif (preg_match('#^iso/([0-9]{4}\.[0-9]{2}\.[0-9]{2})/#', $this->file, $matches)) {
            $releasedate = Database::prepare('
                SELECT
                    created
                FROM
                    releng_releases
                WHERE
                    version = :version
                    AND available = 1
                ');
            $releasedate->bindParam('version', $matches[1], PDO::PARAM_STR);
            $releasedate->execute();
            if ($releasedate->rowCount() == 0) {
                $this->setStatus(Output::NOT_FOUND);
                $this->showFailure('ISO image was not found');
            }
            $this->lastsync = $releasedate->fetchColumn();
        } else {
            $this->lastsync = Input::getTime() - (60 * 60 * 24);
        }
    }

    /**
     * @param string $text
     */
    private function showFailure(string $text)
    {
        echo $text;
        exit();
    }

    public function printPage()
    {
        $this->redirectToUrl($this->getMirror($this->lastsync).$this->file);
    }

    /**
     * @return array
     */
    private function getAvailableArchitectures(): array
    {
        $uniqueArchitectures = array();
        foreach (Config::get('packages', 'repositories') as $architectures) {
            foreach ($architectures as $architecture) {
                $uniqueArchitectures[$architecture] = 1;
            }
        }

        return array_keys($uniqueArchitectures);
    }

    /**
     * @return string
     */
    private function getClientId(): string
    {
        return crc32(Input::getClientIP());
    }

    /**
     * @param int $lastsync
     *
     * @return string
     */
    private function getMirror(int $lastsync): string
    {
        $countryCode = Input::getClientCountryCode();
        if (empty($countryCode)) {
            $countryCode = Config::get('mirrors', 'country');
        }
        $clientId = $this->getClientId();
        $stm = Database::prepare('
            SELECT
                url
            FROM
                mirrors
            WHERE
                lastsync > :lastsync
                AND countryCode = :countryCode
                AND protocol IN ("http", "htttps")
            ORDER BY RAND(:clientId) LIMIT 1
            ');
        $stm->bindParam('lastsync', $lastsync, PDO::PARAM_INT);
        $stm->bindParam('countryCode', $countryCode, PDO::PARAM_STR);
        $stm->bindParam('clientId', $clientId, PDO::PARAM_INT);
        $stm->execute();
        if ($stm->rowCount() == 0) {
            // Let's see if any mirror is recent enough
            $stm = Database::prepare('
                SELECT
                    url
                FROM
                    mirrors
                WHERE
                    lastsync > :lastsync
                    AND protocol IN ("http", "htttps")
                ORDER BY RAND(:clientId) LIMIT 1
                ');
            $stm->bindParam('lastsync', $lastsync, PDO::PARAM_INT);
            $stm->bindParam('clientId', $clientId, PDO::PARAM_INT);
            $stm->execute();
            if ($stm->rowCount() == 0) {
                $this->setStatus(Output::NOT_FOUND);
                $this->showFailure('File was not found');
            }
        }

        return $stm->fetchColumn();
    }
}
