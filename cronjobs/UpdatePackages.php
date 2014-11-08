#!/usr/bin/php -d memory_limit=256M
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

require(__DIR__ . '/../vendor/autoload.php');

use archportal\lib\Config;
use archportal\lib\CronJob;
use archportal\lib\Database;
use archportal\lib\Download;
use archportal\lib\Input;
use archportal\lib\ObjectStore;
use archportal\lib\Package;
use archportal\lib\PackageDatabase;

set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

class UpdatePackages extends CronJob
{

    private $lastMirrorUpdate = 0;
    private $updatedPackages = false;
    /** @var \PDOStatement  */
    private $selectRepoMTime = null;
    /** @var \PDOStatement  */
    private $selectPackageMTime = null;
    /** @var \PDOStatement  */
    private $updateRepoMTime = null;
    /** @var \PDOStatement  */
    private $selectArchId = null;
    /** @var \PDOStatement  */
    private $insertArchName = null;
    /** @var \PDOStatement  */
    private $arches = array();
    /** @var \PDOStatement  */
    private $selectRepoId = null;
    /** @var \PDOStatement  */
    private $insertRepoName = null;
    /** @var \PDOStatement  */
    private $selectPackageId = null;
    /** @var \PDOStatement  */
    private $updatePackage = null;
    /** @var \PDOStatement  */
    private $insertPackage = null;
    /** @var \PDOStatement  */
    private $selectPackager = null;
    /** @var \PDOStatement  */
    private $insertPackager = null;
    private $packagers = array();
    /** @var \PDOStatement  */
    private $selectGroup = null;
    /** @var \PDOStatement  */
    private $insertGroup = null;
    /** @var \PDOStatement  */
    private $cleanupPackageGroup = null;
    /** @var \PDOStatement  */
    private $insertPackageGroup = null;
    private $groups = array();
    /** @var \PDOStatement  */
    private $selectLicense = null;
    /** @var \PDOStatement  */
    private $insertLicense = null;
    /** @var \PDOStatement  */
    private $cleanupPackageLicense = null;
    /** @var \PDOStatement  */
    private $insertPackageLicense = null;
    private $licenses = array();
    /** @var \PDOStatement  */
    private $cleanupRelation = null;
    /** @var \PDOStatement  */
    private $insertRelation = null;
    /** @var \PDOStatement  */
    private $selectFileIndex = null;
    /** @var \PDOStatement  */
    private $insertFileIndex = null;
    /** @var \PDOStatement  */
    private $cleanupFiles = null;
    /** @var \PDOStatement  */
    private $insertFiles = null;
    /** @var \PDOStatement  */
    private $insertPackageFileIndex = null;
    /** @var \PDOStatement  */
    private $cleanupPackageFileIndex = null;
    private $files = array();
    private $contentTables = array(
        'architectures',
        'files',
        'file_index',
        'groups',
        'licenses',
        'packagers',
        'packages',
        'package_file_index',
        'package_group',
        'package_license',
        'package_relation',
        'repositories'
    );

    public function execute()
    {
        if (count(getopt('pr', array('purge', 'reset'))) == 0 && !$this->checkLastMirrorUpdate()) {
            $this->printDebug("No updated packages available...");

            return;
        }

        try {
            Database::beginTransaction();

            if (count(getopt('p', array('purge'))) > 0) {
                $this->purgeDatabase();
            } elseif (count(getopt('r', array('reset'))) > 0) {
                $this->resetDatabase();
            }

            $this->prepareQueries();

            foreach (Config::get('packages', 'repositories') as $repo => $arches) {
                foreach ($arches as $arch) {
                    $this->printDebug('Processing [' . $repo . '] (' . $arch . ')');
                    $archId = $this->getArchId($arch);
                    $repoId = $this->getRepoId($repo, $archId);

                    $this->selectRepoMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                    $this->selectRepoMTime->execute();
                    $repoMTime = $this->selectRepoMTime->fetchColumn();

                    $this->selectPackageMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                    $this->selectPackageMTime->execute();
                    $packageMTime = $this->selectPackageMTime->fetchColumn();

                    $this->printDebug("\tDownloading...");
                    $packages = new PackageDatabase($repo, $arch, $repoMTime, $packageMTime);

                    if ($packages->getMTime() > $repoMTime && Input::getTime() - $packages->getMTime() > Config::get('packages', 'delay')) {
                        $packageCount = 0;
                        foreach ($packages as $package) {
                            $this->printProgress(++$packageCount, $packages->getNewPackageCount(), "\tReading packages: ");
                            $this->updatePackage($repoId, $package);
                        }

                        $this->printDebug("\tCleaning up obsolete packages...");
                        $this->cleanupObsoletePackages($repoId, $packageMTime, $packages->getOldPackageNames());

                        $this->updateRepoMTime->bindValue('mtime', $packages->getMTime(), PDO::PARAM_INT);
                        $this->updateRepoMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                        $this->updateRepoMTime->execute();
                    }
                }
                $this->groups = array();
                $this->files = array();
            }

            $this->printDebug("Cleaning up obsolete repositories...");
            $this->cleanupObsoleteRepositories();

            if ($this->updatedPackages) {
                $this->printDebug("Cleaning up obsolete database entries...");
                $this->cleanupDatabase();
                $this->printDebug("Resolving package relations...");
                $this->resolveRelations();
            }

            Database::commit();
            $this->updateLastMirrorUpdate();
        } catch (RuntimeException $e) {
            Database::rollBack();
            $this->printError('UpdatePackages failed at ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage());
        }
    }

    private function checkLastMirrorUpdate()
    {
        $lastLocalUpdate = ObjectStore::getObject('UpdatePackages:lastupdate');
        $download = new Download(Config::get('packages', 'mirror') . 'lastupdate');
        $this->lastMirrorUpdate = file_get_contents($download->getFile());

        return $this->lastMirrorUpdate !== $lastLocalUpdate;
    }

    private function updateLastMirrorUpdate()
    {
        ObjectStore::addObject('UpdatePackages:lastupdate', $this->lastMirrorUpdate);
    }

    private function purgeDatabase()
    {
        $rowsTotal = 0;
        foreach ($this->contentTables as $table) {
            $rowsTotal += (int) Database::query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        }
        $rowCount = 0;
        foreach ($this->contentTables as $table) {
            $rowCount += Database::exec('DELETE FROM `' . $table . '`');
            $this->printProgress($rowCount, $rowsTotal, "Purging database: ");
        }
        ObjectStore::addObject('UpdatePackages:lastupdate', 0);
    }

    private function resetDatabase()
    {
        Database::commit();

        $tablesTotal = count($this->contentTables);
        $tableCount = 0;
        foreach ($this->contentTables as $table) {
            Database::exec('TRUNCATE TABLE `' . $table . '`');
            $this->printProgress( ++$tableCount, $tablesTotal, "Resetting database: ");
        }
        ObjectStore::addObject('UpdatePackages:lastupdate', 0);

        Database::beginTransaction();
    }

    private function prepareQueries()
    {
        // arches
        $this->selectArchId = Database::prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :name
            ');
        $this->insertArchName = Database::prepare('
            INSERT INTO
                architectures
            SET
                name = :name
            ');

        //repos
        $this->selectRepoId = Database::prepare('
            SELECT
                id
            FROM
                repositories
            WHERE
                name = :name
                AND arch = :arch
            ');
        $this->insertRepoName = Database::prepare('
            INSERT INTO
                repositories
            SET
                name = :name,
                arch = :arch,
                testing = :testing
            ');

        // mtime
        $this->selectRepoMTime = Database::prepare('
            SELECT
                mtime
            FROM
                repositories
            WHERE
                id = :repoId
            ');
        $this->updateRepoMTime = Database::prepare('
            UPDATE
                repositories
            SET
                mtime = :mtime
            WHERE
                id = :repoId
            ');
        $this->selectPackageMTime = Database::prepare('
            SELECT
                MAX(mtime)
            FROM
                packages
            WHERE
                repository = :repoId
            ');

        // packages
        $this->selectPackageId = Database::prepare('
            SELECT
                id
            FROM
                packages
            WHERE
                repository = :repoId
                AND arch = :archId
                AND name = :pkgname
            ');
        $this->updatePackage = Database::prepare('
            UPDATE
                packages
            SET
                filename = :filename,
                name = :name,
                base = :base,
                `version` = :version,
                `desc` = :desc,
                csize = :csize,
                isize = :isize,
                md5sum = :md5sum,
                sha256sum = :sha256sum,
                pgpsig = :pgpsig,
                url = :url,
                arch = :arch,
                builddate = :builddate,
                mtime = :mtime,
                packager = :packager,
                repository = :repoId
            WHERE
                id = :id
            ');
        $this->insertPackage = Database::prepare('
            INSERT INTO
                packages
            SET
                filename = :filename,
                name = :name,
                base = :base,
                `version` = :version,
                `desc` = :desc,
                csize = :csize,
                isize = :isize,
                md5sum = :md5sum,
                sha256sum = :sha256sum,
                pgpsig = :pgpsig,
                url = :url,
                arch = :arch,
                builddate = :builddate,
                mtime = :mtime,
                packager = :packager,
                repository = :repoId
            ');

        // packagers
        $this->selectPackager = Database::prepare('
            SELECT
                id
            FROM
                packagers
            WHERE
                name = :name
                AND email = :email
            ');
        $this->insertPackager = Database::prepare('
            INSERT INTO
                packagers
            SET
                name = :name,
                email = :email
            ');

        // groups
        $this->selectGroup = Database::prepare('
            SELECT
                id
            FROM
                groups
            WHERE
                name = :name
            ');
        $this->insertGroup = Database::prepare('
            INSERT INTO
                groups
            SET
                name = :name
            ');
        $this->cleanupPackageGroup = Database::prepare('
            DELETE FROM
                package_group
            WHERE
                package = :package
            ');
        $this->insertPackageGroup = Database::prepare('
            INSERT INTO
                package_group
            SET
                package = :package,
                `group` = :group
            ');

        // licenses
        $this->selectLicense = Database::prepare('
            SELECT
                id
            FROM
                licenses
            WHERE
                name = :name
            ');
        $this->insertLicense = Database::prepare('
            INSERT INTO
                licenses
            SET
                name = :name
            ');
        $this->cleanupPackageLicense = Database::prepare('
            DELETE FROM
                package_license
            WHERE
                package = :package
            ');
        $this->insertPackageLicense = Database::prepare('
            INSERT INTO
                package_license
            SET
                package = :package,
                license = :license
            ');

        // files
        if (Config::get('packages', 'files')) {
            $this->selectFileIndex = Database::prepare('
                SELECT
                    id
                FROM
                    file_index
                WHERE
                    name = :name
                ');
            $this->insertFileIndex = Database::prepare('
                INSERT INTO
                    file_index
                SET
                    name = :name
                ');
            $this->cleanupPackageFileIndex = Database::prepare('
                DELETE FROM
                    package_file_index
                WHERE
                    package = :package
                ');
            $this->cleanupFiles = Database::prepare('
                DELETE FROM
                    files
                WHERE
                    package = :package
                ');
            $this->insertFiles = Database::prepare('
                INSERT INTO
                    files
                SET
                    package = :package,
                    path = :path
                ');
            $this->insertPackageFileIndex = Database::prepare('
                INSERT INTO
                    package_file_index
                SET
                    package = :package,
                    file_index = :file
                ');
        }

        // relations
        $this->cleanupRelation = Database::prepare('
            DELETE FROM
                package_relation
            WHERE
                packageId = :packageId
                AND type = :type
            ');
        $this->insertRelation = Database::prepare('
            INSERT INTO
                package_relation
            SET
                packageId = :packageId,
                dependsName = :dependsName,
                dependsVersion =:dependsVersion,
                type = :type
            ');
    }

    private function getArchId($archName)
    {
        if (!isset($this->arches[$archName])) {
            $archHtml = htmlspecialchars($archName);
            $this->selectArchId->bindParam('name', $archHtml, PDO::PARAM_STR);
            $this->selectArchId->execute();
            $id = $this->selectArchId->fetchColumn();
            if ($id === false) {
                $this->insertArchName->bindParam('name', $archHtml, PDO::PARAM_STR);
                $this->insertArchName->execute();
                $id = Database::lastInsertId();
            }
            $this->arches[$archName] = $id;
        }

        return $this->arches[$archName];
    }

    private function getRepoId($repoName, $archId)
    {
        $repoName = htmlspecialchars($repoName);
        $this->selectRepoId->bindParam('name', $repoName, PDO::PARAM_STR);
        $this->selectRepoId->bindParam('arch', $archId, PDO::PARAM_INT);
        $this->selectRepoId->execute();
        $id = $this->selectRepoId->fetchColumn();
        if ($id === false) {
            $this->insertRepoName->bindParam('name', $repoName, PDO::PARAM_STR);
            $this->insertRepoName->bindParam('arch', $archId, PDO::PARAM_INT);
            $this->insertRepoName->bindValue('testing', (preg_match('/(-|^)testing$/', $repoName) > 0 ? 1 : 0), PDO::PARAM_INT);
            $this->insertRepoName->execute();
            $id = Database::lastInsertId();
        }

        return $id;
    }

    private function getPackagerId($packager)
    {
        if (!isset($this->packagers[$packager])) {
            preg_match('/([^<>]+)(?:<(.+?)>)?/', $packager, $matches);
            $name = htmlspecialchars(trim(!empty($matches[1]) ? $matches[1] : $packager));
            $email = htmlspecialchars(trim(isset($matches[2]) ? $matches[2] : ''));
            $this->selectPackager->bindParam('name', $name, PDO::PARAM_STR);
            $this->selectPackager->bindParam('email', $email, PDO::PARAM_STR);
            $this->selectPackager->execute();
            $id = $this->selectPackager->fetchColumn();
            if ($id === false) {
                $this->insertPackager->bindParam('name', $name, PDO::PARAM_STR);
                $this->insertPackager->bindParam('email', $email, PDO::PARAM_STR);
                $this->insertPackager->execute();
                $id = Database::lastInsertId();
            }
            $this->packagers[$packager] = $id;
        }

        return $this->packagers[$packager];
    }

    private function addPackageToGroups($packageId, $groups)
    {
        $this->cleanupPackageGroup->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupPackageGroup->execute();
        foreach ($groups as $group) {
            $this->insertPackageGroup->bindParam('package', $packageId, PDO::PARAM_INT);
            $this->insertPackageGroup->bindValue('group', $this->getGroupID($group), PDO::PARAM_INT);
            $this->insertPackageGroup->execute();
        }
    }

    private function getGroupID($groupName)
    {
        if (!isset($this->groups[$groupName])) {
            $htmlGroup = htmlspecialchars($groupName);
            $this->selectGroup->bindParam('name', $htmlGroup, PDO::PARAM_STR);
            $this->selectGroup->execute();
            $id = $this->selectGroup->fetchColumn();
            if ($id === false) {
                $this->insertGroup->bindParam('name', $htmlGroup, PDO::PARAM_STR);
                $this->insertGroup->execute();
                $id = Database::lastInsertId();
            }
            $this->groups[$groupName] = $id;
        }

        return $this->groups[$groupName];
    }

    private function addPackageToLicenses($packageId, $licenses)
    {
        $this->cleanupPackageLicense->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupPackageLicense->execute();
        foreach ($licenses as $license) {
            $this->insertPackageLicense->bindParam('package', $packageId, PDO::PARAM_INT);
            $this->insertPackageLicense->bindValue('license', $this->getLicenseID($license), PDO::PARAM_INT);
            $this->insertPackageLicense->execute();
        }
    }

    private function getLicenseID($licenseName)
    {
        if (!isset($this->licenses[$licenseName])) {
            $htmlLicense = htmlspecialchars($licenseName);
            $this->selectLicense->bindParam('name', $htmlLicense, PDO::PARAM_STR);
            $this->selectLicense->execute();
            $id = $this->selectLicense->fetchColumn();
            if ($id === false) {
                $this->insertLicense->bindParam('name', $htmlLicense, PDO::PARAM_STR);
                $this->insertLicense->execute();
                $id = Database::lastInsertId();
            }
            $this->licenses[$licenseName] = $id;
        }

        return $this->licenses[$licenseName];
    }

    private function addRelation($relations, $packageId, $type)
    {
        $this->cleanupRelation->bindParam('packageId', $packageId, PDO::PARAM_INT);
        $this->cleanupRelation->bindParam('type', $type, PDO::PARAM_STR);
        $this->cleanupRelation->execute();
        foreach ($relations as $relation) {
            if (preg_match('/^([\w-]+?)((?:<|<=|=|>=|>)+[\w\.:]+)/', $relation, $matches) > 0) {
                $relationName = htmlspecialchars($matches[1]);
                $relationVersion = htmlspecialchars($matches[2]);
            } elseif (preg_match('/^([\w-]+)/', $relation, $matches) > 0) {
                $relationName = htmlspecialchars($matches[1]);
                $relationVersion = null;
            } else {
                $relationName = htmlspecialchars($relation);
                $relationVersion = null;
            }
            $this->insertRelation->bindParam('packageId', $packageId, PDO::PARAM_INT);
            $this->insertRelation->bindParam('dependsName', $relationName, PDO::PARAM_STR);
            $this->insertRelation->bindParam('dependsVersion', $relationVersion, PDO::PARAM_STR);
            $this->insertRelation->bindParam('type', $type, PDO::PARAM_STR);
            $this->insertRelation->execute();
        }
    }

    private function getFileIndexID($fileName)
    {
        if (!isset($this->files[$fileName])) {
            $htmlFile = htmlspecialchars($fileName);
            $this->selectFileIndex->bindParam('name', $htmlFile, PDO::PARAM_STR);
            $this->selectFileIndex->execute();
            $id = $this->selectFileIndex->fetchColumn();
            if ($id === false) {
                $this->insertFileIndex->bindParam('name', $htmlFile, PDO::PARAM_STR);
                $this->insertFileIndex->execute();
                $id = Database::lastInsertId();
            }
            $this->files[$fileName] = $id;
        }

        return $this->files[$fileName];
    }

    private function insertFiles($files, $packageId)
    {
        $this->cleanupPackageFileIndex->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupPackageFileIndex->execute();

        $this->cleanupFiles->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupFiles->execute();

        foreach ($files as $file) {
            $this->insertFiles->bindParam('package', $packageId, PDO::PARAM_INT);
            $this->insertFiles->bindValue('path', htmlspecialchars($file), PDO::PARAM_STR);
            $this->insertFiles->execute();
            // skip directories (which end with /)
            if (substr($file, -1) != '/') {
                $filename = basename($file);
                if (strlen($filename) > 2) {
                    $this->insertPackageFileIndex->bindParam('package', $packageId, PDO::PARAM_INT);
                    $this->insertPackageFileIndex->bindValue('file', $this->getFileIndexID($filename), PDO::PARAM_INT);
                    $this->insertPackageFileIndex->execute();
                }
            }
        }
    }

    private function updatePackage($repoId, Package $package)
    {
        $packageName = htmlspecialchars($package->getName());
        $packageArch = $this->getArchId($package->getArch());

        $this->selectPackageId->bindParam('archId', $packageArch, PDO::PARAM_INT);
        $this->selectPackageId->bindParam('repoId', $repoId, PDO::PARAM_INT);
        $this->selectPackageId->bindParam('pkgname', $packageName, PDO::PARAM_STR);
        $this->selectPackageId->execute();
        $packageId = $this->selectPackageId->fetchColumn();

        if ($packageId !== false) {
            $packageStm = $this->updatePackage;
            $packageStm->bindParam('id', $packageId, PDO::PARAM_INT);
        } else {
            $packageStm = $this->insertPackage;
        }

        $packageStm->bindValue('filename', htmlspecialchars($package->getFileName()), PDO::PARAM_STR);
        $packageStm->bindParam('name', $packageName, PDO::PARAM_STR);
        $packageStm->bindValue('base', htmlspecialchars($package->getBase()), PDO::PARAM_STR);
        $packageStm->bindValue('version', htmlspecialchars($package->getVersion()), PDO::PARAM_STR);
        $packageStm->bindValue('desc', htmlspecialchars($package->getDescription()), PDO::PARAM_STR);
        $packageStm->bindValue('csize', $package->getCompressedSize(), PDO::PARAM_INT);
        $packageStm->bindValue('isize', $package->getInstalledSize(), PDO::PARAM_INT);
        $packageStm->bindValue('md5sum', $package->getMD5SUM(), PDO::PARAM_STR);
        $packageStm->bindValue('sha256sum', $package->getSHA256SUM(), PDO::PARAM_STR);
        $packageStm->bindValue('pgpsig', base64_decode($package->getPGPSignature(), true), PDO::PARAM_STR);
        $packageStm->bindValue('url', htmlspecialchars($package->getURL()), PDO::PARAM_STR);
        $packageStm->bindParam('arch', $packageArch, PDO::PARAM_INT);
        $packageStm->bindValue('builddate', $package->getBuildDate(), PDO::PARAM_INT);
        $packageStm->bindValue('mtime', $package->getMTime(), PDO::PARAM_INT);
        $packageStm->bindValue('packager', $this->getPackagerId($package->getPackager()), PDO::PARAM_INT);
        $packageStm->bindParam('repoId', $repoId, PDO::PARAM_INT);
        $packageStm->execute();

        if ($packageId === false) {
            $packageId = Database::lastInsertId();
        }

        $this->addPackageToGroups($packageId, $package->getGroups());
        $this->addPackageToLicenses($packageId, $package->getLicenses());

        $this->addRelation($package->getReplaces(), $packageId, 'replaces');
        $this->addRelation($package->getDepends(), $packageId, 'depends');
        $this->addRelation($package->getOptDepends(), $packageId, 'optdepends');
        $this->addRelation($package->getConflicts(), $packageId, 'conflicts');
        $this->addRelation($package->getProvides(), $packageId, 'provides');
        $this->addRelation($package->getMakeDepends(), $packageId, 'makedepends');
        $this->addRelation($package->getCheckDepends(), $packageId, 'checkdepends');

        if (Config::get('packages', 'files')) {
            $this->insertFiles($package->getFiles(), $packageId);
        }

        $this->updatedPackages = true;
    }

    private function resolveRelations()
    {
        // Reset all relations
        Database::query('
            UPDATE
                package_relation
            SET
                dependsId = NULL
            ');

        // Look for depends within the same repo
        Database::query('
            UPDATE
                package_relation,
                packages,
                packages AS deppkg,
                repositories
            SET
                package_relation.dependsId = deppkg.id
            WHERE
                package_relation.dependsId IS NULL
                AND repositories.id = packages.repository
                AND package_relation.packageId = packages.id
                AND repositories.id = deppkg.repository
                AND deppkg.name = package_relation.dependsName
            ');

        // Look for depends in other repos except testing repos
        Database::query('
            UPDATE
                package_relation,
                packages,
                packages AS deppkg,
                repositories,
                repositories AS deprepo
            SET
                package_relation.dependsId = deppkg.id
            WHERE
                package_relation.dependsId IS NULL
                AND repositories.arch = deprepo.arch
                AND repositories.id = packages.repository
                AND package_relation.packageId = packages.id
                AND deprepo.id = deppkg.repository
                AND deprepo.testing = 0
                AND deppkg.name = package_relation.dependsName
            ');
    }

    private function cleanupObsoletePackages($repoId, $packageMTime, $allPackages)
    {
        $cleanupPackages = Database::prepare('
            DELETE FROM
                packages
            WHERE
                id = :packageId
            ');
        $cleanupRelations = Database::prepare('
            DELETE FROM
                package_relation
            WHERE
                packageId = :packageId
            ');
        if (Config::get('packages', 'files')) {
            $cleanupFiles = Database::prepare('
                DELETE FROM
                    files
                WHERE
                    package = :packageId
                ');
            $cleanupPackageFileIndex = Database::prepare('
                DELETE FROM
                    package_file_index
                WHERE
                    package = :packageId
                ');
        }
        $cleanupPackageGroup = Database::prepare('
            DELETE FROM
                package_group
            WHERE
                package = :packageId
            ');
        $cleanupPackageLicense = Database::prepare('
            DELETE FROM
                package_license
            WHERE
                package = :packageId
            ');
        $repoPackages = Database::prepare('
            SELECT
                id,
                name
            FROM
                packages
            WHERE
                repository = :repoId
                AND mtime <= :mtime
            ');
        $repoPackages->bindValue('repoId', $repoId, PDO::PARAM_INT);
        $repoPackages->bindValue('mtime', $packageMTime, PDO::PARAM_INT);
        $repoPackages->execute();
        foreach ($repoPackages as $repoPackage) {
            if (!in_array($repoPackage['name'], $allPackages)) {
                $cleanupPackages->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                $cleanupPackages->execute();
                $cleanupRelations->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                $cleanupRelations->execute();
                if (isset($cleanupFiles) && isset($cleanupPackageFileIndex)) {
                    $cleanupFiles->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                    $cleanupFiles->execute();
                    $cleanupPackageFileIndex->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                    $cleanupPackageFileIndex->execute();
                }
                $cleanupPackageGroup->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                $cleanupPackageGroup->execute();
                $cleanupPackageLicense->bindValue('packageId', $repoPackage['id'], PDO::PARAM_INT);
                $cleanupPackageLicense->execute();
                $this->updatedPackages = true;
            }
        }
    }

    private function cleanupObsoleteRepositories()
    {
        $repos = Database::query('
            SELECT
                repositories.id,
                repositories.name,
                architectures.name AS arch
            FROM
                repositories
                JOIN architectures
                ON architectures.id = repositories.arch
            ')->fetchAll();
        $configRepos = Config::get('packages', 'repositories');
        foreach ($repos as $repo) {
            if (!isset($configRepos[$repo['name']]) || !in_array($repo['arch'], $configRepos[$repo['name']])) {
                $this->printDebug("\tRemoving repository [$repo[name]] ($repo[arch])");
                $this->cleanupObsoletePackages($repo['id'], time(), array());
                Database::query('
                    DELETE FROM
                        repositories
                    WHERE
                        id = ' . $repo['id'] . '
                    ');
                $this->updatedPackages = true;
            }
        }
    }

    private function cleanupDatabase()
    {
        Database::query('
            DELETE FROM
                groups
            WHERE
                NOT EXISTS (
                    SELECT * FROM package_group WHERE package_group.group = groups.id
                )
            ');
        Database::query('
            DELETE FROM
                licenses
            WHERE
                NOT EXISTS (
                    SELECT * FROM package_license WHERE package_license.license = licenses.id
                )
            ');
        Database::query('
            DELETE FROM
                packagers
            WHERE
                NOT EXISTS (
                    SELECT * FROM packages WHERE packages.packager = packagers.id
                )
            ');
        Database::query('
            DELETE FROM
                architectures
            WHERE
                NOT EXISTS (
                    SELECT * FROM packages WHERE packages.arch = architectures.id
                )
            ');
        if (Config::get('packages', 'files')) {
            Database::query('
                DELETE FROM
                    file_index
                WHERE
                    NOT EXISTS (
                        SELECT * FROM package_file_index WHERE package_file_index.file_index = file_index.id
                    )
                ');
        }
    }

}

UpdatePackages::run();
