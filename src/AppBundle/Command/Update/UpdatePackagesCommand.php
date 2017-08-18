<?php

namespace AppBundle\Command\Update;

use archportal\lib\Config;
use archportal\lib\Download;
use archportal\lib\ObjectStore;
use archportal\lib\Package;
use archportal\lib\PackageDatabase;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use PDO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    private $lastMirrorUpdate = 0;
    private $updatedPackages = false;
    /** @var Statement */
    private $selectRepoMTime = null;
    /** @var Statement */
    private $selectPackageMTime = null;
    /** @var Statement */
    private $updateRepoMTime = null;
    /** @var Statement */
    private $selectArchId = null;
    /** @var Statement */
    private $insertArchName = null;
    /** @var array */
    private $arches = array();
    /** @var Statement */
    private $selectRepoId = null;
    /** @var Statement */
    private $insertRepoName = null;
    /** @var Statement */
    private $selectPackageId = null;
    /** @var Statement */
    private $updatePackage = null;
    /** @var Statement */
    private $insertPackage = null;
    /** @var Statement */
    private $selectPackager = null;
    /** @var Statement */
    private $insertPackager = null;
    private $packagers = array();
    /** @var Statement */
    private $selectGroup = null;
    /** @var Statement */
    private $insertGroup = null;
    /** @var Statement */
    private $cleanupPackageGroup = null;
    /** @var Statement */
    private $insertPackageGroup = null;
    private $groups = array();
    /** @var Statement */
    private $selectLicense = null;
    /** @var Statement */
    private $insertLicense = null;
    /** @var Statement */
    private $cleanupPackageLicense = null;
    /** @var Statement */
    private $insertPackageLicense = null;
    private $licenses = array();
    /** @var Statement */
    private $cleanupRelation = null;
    /** @var Statement */
    private $insertRelation = null;
    /** @var Statement */
    private $selectFileIndex = null;
    /** @var Statement */
    private $insertFileIndex = null;
    /** @var Statement */
    private $cleanupFiles = null;
    /** @var Statement */
    private $insertFiles = null;
    /** @var Statement */
    private $insertPackageFileIndex = null;
    /** @var Statement */
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
        'repositories',
    );
    /** @var Connection */
    private $database;
    /** @var ObjectStore */
    private $objectStore;

    /**
     * @param Connection $connection
     * @param ObjectStore $objectStore
     */
    public function __construct(Connection $connection, ObjectStore $objectStore)
    {
        parent::__construct();
        $this->database = $connection;
        $this->objectStore = $objectStore;
    }

    protected function configure()
    {
        $this
            ->setName('app:update:packages')
            ->addOption('purge', 'p')
            ->addOption('reset', 'r');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();

        if (!$input->getOption('purge') && !$input->getOption('reset') && !$this->checkLastMirrorUpdate()) {
            $this->printDebug('No updated packages available...', $output);

            return;
        }

        try {
            ini_set('memory_limit', '-1');
            $this->database->beginTransaction();

            if ($input->getOption('purge')) {
                $this->purgeDatabase($output);
            } elseif ($input->getOption('reset')) {
                $this->resetDatabase($output);
            }

            $this->prepareQueries();

            foreach (Config::get('packages', 'repositories') as $repo => $arches) {
                foreach ($arches as $arch) {
                    $this->printDebug('Processing [' . $repo . '] (' . $arch . ')', $output);
                    $archId = $this->getArchId($arch);
                    $repoId = $this->getRepoId($repo, $archId);

                    $this->selectRepoMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                    $this->selectRepoMTime->execute();
                    $repoMTime = $this->selectRepoMTime->fetchColumn();

                    $this->selectPackageMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                    $this->selectPackageMTime->execute();
                    $packageMTime = (int)$this->selectPackageMTime->fetchColumn();

                    $this->printDebug("\tDownloading...", $output);
                    $packages = new PackageDatabase($repo, $arch, $repoMTime, $packageMTime);

                    if ($packages->getMTime() > $repoMTime && time() - $packages->getMTime() > Config::get(
                        'packages',
                        'delay'
                    )
                    ) {
                        if (!$output->isQuiet()) {
                            $progress = new ProgressBar($output, $packages->getNewPackageCount());
                            $progress->setFormatDefinition('minimal', "\tReading packages: %percent%%");
                            $progress->setFormat('minimal');
                            $progress->start();
                        }
                        foreach ($packages as $package) {
                            if (isset($progress)) {
                                $progress->advance();
                            }
                            $this->updatePackage($repoId, $package);
                        }
                        if (isset($progress)) {
                            $progress->finish();
                            $output->writeln('');
                        }

                        $this->printDebug("\tCleaning up obsolete packages...", $output);
                        $this->cleanupObsoletePackages($repoId, $packageMTime, $packages->getOldPackageNames());

                        $this->updateRepoMTime->bindValue('mtime', $packages->getMTime(), PDO::PARAM_INT);
                        $this->updateRepoMTime->bindParam('repoId', $repoId, PDO::PARAM_INT);
                        $this->updateRepoMTime->execute();
                    }
                }
                $this->groups = array();
                $this->files = array();
            }

            $this->printDebug('Cleaning up obsolete repositories...', $output);
            $this->cleanupObsoleteRepositories($output);

            if ($this->updatedPackages) {
                $this->printDebug('Cleaning up obsolete database entries...', $output);
                $this->cleanupDatabase();
                $this->printDebug('Resolving package relations...', $output);
                $this->resolveRelations();
            }

            $this->database->commit();
            $this->updateLastMirrorUpdate();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            $this->printError(
                'UpdatePackages failed at ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage(),
                $output
            );
        }
    }

    /**
     * @param string $text
     * @param OutputInterface $output
     */
    private function printDebug(string $text, OutputInterface $output)
    {
        if (!$output->isQuiet()) {
            $output->writeln($text);
        }
    }

    /**
     * @param string $text
     * @param OutputInterface $output
     */
    private function printError(string $text, OutputInterface $output)
    {
        $output->writeln($text);
    }

    /**
     * @return bool
     */
    private function checkLastMirrorUpdate(): bool
    {
        $lastLocalUpdate = $this->objectStore->getObject('UpdatePackages:lastupdate');
        $download = new Download(Config::get('packages', 'mirror') . 'lastupdate');
        $this->lastMirrorUpdate = (int)file_get_contents($download->getFile());

        return $this->lastMirrorUpdate !== $lastLocalUpdate;
    }

    private function updateLastMirrorUpdate()
    {
        $this->objectStore->addObject('UpdatePackages:lastupdate', $this->lastMirrorUpdate);
    }

    private function purgeDatabase(OutputInterface $output)
    {
        if (!$output->isQuiet()) {
            $rowsTotal = 0;
            foreach ($this->contentTables as $table) {
                $rowsTotal += (int)$this->database->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            }
            $progress = new ProgressBar($output, $rowsTotal);
            $progress->setFormatDefinition('minimal', 'Purging databas: %percent%%');
            $progress->setFormat('minimal');
            $progress->start();
        }
        foreach ($this->contentTables as $table) {
            $rowCount = $this->database->exec('DELETE FROM `' . $table . '`');
            if (isset($progress)) {
                $progress->advance($rowCount);
            }
        }
        if (isset($progress)) {
            $progress->finish();
            $output->writeln('');
        }
        $this->objectStore->addObject('UpdatePackages:lastupdate', 0);
    }

    private function resetDatabase(OutputInterface $output)
    {
        $this->database->commit();

        if (!$output->isQuiet()) {
            $tablesTotal = count($this->contentTables);
            $progress = new ProgressBar($output, $tablesTotal);
            $progress->setFormatDefinition('minimal', 'Resetting databas: %percent%%');
            $progress->setFormat('minimal');
            $progress->start();
        }
        foreach ($this->contentTables as $table) {
            $this->database->exec('TRUNCATE TABLE `' . $table . '`');
            if (isset($progress)) {
                $progress->advance();
            }
        }
        if (isset($progress)) {
            $progress->finish();
            $output->writeln('');
        }
        $this->objectStore->addObject('UpdatePackages:lastupdate', 0);

        $this->database->beginTransaction();
    }

    private function prepareQueries()
    {
        // arches
        $this->selectArchId = $this->database->prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :name
            ');
        $this->insertArchName = $this->database->prepare('
            INSERT INTO
                architectures
            SET
                name = :name
            ');

        //repos
        $this->selectRepoId = $this->database->prepare('
            SELECT
                id
            FROM
                repositories
            WHERE
                name = :name
                AND arch = :arch
            ');
        $this->insertRepoName = $this->database->prepare('
            INSERT INTO
                repositories
            SET
                name = :name,
                arch = :arch,
                testing = :testing
            ');

        // mtime
        $this->selectRepoMTime = $this->database->prepare('
            SELECT
                mtime
            FROM
                repositories
            WHERE
                id = :repoId
            ');
        $this->updateRepoMTime = $this->database->prepare('
            UPDATE
                repositories
            SET
                mtime = :mtime
            WHERE
                id = :repoId
            ');
        $this->selectPackageMTime = $this->database->prepare('
            SELECT
                COALESCE(MAX(mtime), 0)
            FROM
                packages
            WHERE
                repository = :repoId
            ');

        // packages
        $this->selectPackageId = $this->database->prepare('
            SELECT
                id
            FROM
                packages
            WHERE
                repository = :repoId
                AND arch = :archId
                AND name = :pkgname
            ');
        $this->updatePackage = $this->database->prepare('
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
        $this->insertPackage = $this->database->prepare('
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
        $this->selectPackager = $this->database->prepare('
            SELECT
                id
            FROM
                packagers
            WHERE
                name = :name
                AND email = :email
            ');
        $this->insertPackager = $this->database->prepare('
            INSERT INTO
                packagers
            SET
                name = :name,
                email = :email
            ');

        // groups
        $this->selectGroup = $this->database->prepare('
            SELECT
                id
            FROM
                groups
            WHERE
                name = :name
            ');
        $this->insertGroup = $this->database->prepare('
            INSERT INTO
                groups
            SET
                name = :name
            ');
        $this->cleanupPackageGroup = $this->database->prepare('
            DELETE FROM
                package_group
            WHERE
                package = :package
            ');
        $this->insertPackageGroup = $this->database->prepare('
            INSERT INTO
                package_group
            SET
                package = :package,
                `group` = :group
            ');

        // licenses
        $this->selectLicense = $this->database->prepare('
            SELECT
                id
            FROM
                licenses
            WHERE
                name = :name
            ');
        $this->insertLicense = $this->database->prepare('
            INSERT INTO
                licenses
            SET
                name = :name
            ');
        $this->cleanupPackageLicense = $this->database->prepare('
            DELETE FROM
                package_license
            WHERE
                package = :package
            ');
        $this->insertPackageLicense = $this->database->prepare('
            INSERT INTO
                package_license
            SET
                package = :package,
                license = :license
            ');

        // files
        if (Config::get('packages', 'files')) {
            $this->selectFileIndex = $this->database->prepare('
                SELECT
                    id
                FROM
                    file_index
                WHERE
                    name = :name
                ');
            $this->insertFileIndex = $this->database->prepare('
                INSERT INTO
                    file_index
                SET
                    name = :name
                ');
            $this->cleanupPackageFileIndex = $this->database->prepare('
                DELETE FROM
                    package_file_index
                WHERE
                    package = :package
                ');
            $this->cleanupFiles = $this->database->prepare('
                DELETE FROM
                    files
                WHERE
                    package = :package
                ');
            $this->insertFiles = $this->database->prepare('
                INSERT INTO
                    files
                SET
                    package = :package,
                    path = :path
                ');
            $this->insertPackageFileIndex = $this->database->prepare('
                INSERT INTO
                    package_file_index
                SET
                    package = :package,
                    file_index = :file
                ');
        }

        // relations
        $this->cleanupRelation = $this->database->prepare('
            DELETE FROM
                package_relation
            WHERE
                packageId = :packageId
                AND type = :type
            ');
        $this->insertRelation = $this->database->prepare('
            INSERT INTO
                package_relation
            SET
                packageId = :packageId,
                dependsName = :dependsName,
                dependsVersion =:dependsVersion,
                type = :type
            ');
    }

    /**
     * @param string $archName
     *
     * @return int
     */
    private function getArchId(string $archName): int
    {
        if (!isset($this->arches[$archName])) {
            $archHtml = htmlspecialchars($archName);
            $this->selectArchId->bindParam('name', $archHtml, PDO::PARAM_STR);
            $this->selectArchId->execute();
            $id = $this->selectArchId->fetchColumn();
            if ($id === false) {
                $this->insertArchName->bindParam('name', $archHtml, PDO::PARAM_STR);
                $this->insertArchName->execute();
                $id = $this->database->lastInsertId();
            }
            $this->arches[$archName] = (int)$id;
        }

        return $this->arches[$archName];
    }

    /**
     * @param string $repoName
     * @param int $archId
     *
     * @return int
     */
    private function getRepoId(string $repoName, int $archId): int
    {
        $repoName = htmlspecialchars($repoName);
        $this->selectRepoId->bindParam('name', $repoName, PDO::PARAM_STR);
        $this->selectRepoId->bindParam('arch', $archId, PDO::PARAM_INT);
        $this->selectRepoId->execute();
        $id = $this->selectRepoId->fetchColumn();
        if ($id === false) {
            $this->insertRepoName->bindParam('name', $repoName, PDO::PARAM_STR);
            $this->insertRepoName->bindParam('arch', $archId, PDO::PARAM_INT);
            $this->insertRepoName->bindValue(
                'testing',
                (preg_match('/(-|^)testing$/', $repoName) > 0 ? 1 : 0),
                PDO::PARAM_INT
            );
            $this->insertRepoName->execute();
            $id = $this->database->lastInsertId();
        }

        return (int)$id;
    }

    /**
     * @param string $packager
     *
     * @return int
     */
    private function getPackagerId(string $packager): int
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
                $id = $this->database->lastInsertId();
            }
            $this->packagers[$packager] = (int)$id;
        }

        return $this->packagers[$packager];
    }

    /**
     * @param int $packageId
     * @param array $groups
     */
    private function addPackageToGroups(int $packageId, array $groups)
    {
        $this->cleanupPackageGroup->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupPackageGroup->execute();
        foreach ($groups as $group) {
            $this->insertPackageGroup->bindParam('package', $packageId, PDO::PARAM_INT);
            $this->insertPackageGroup->bindValue('group', $this->getGroupID($group), PDO::PARAM_INT);
            $this->insertPackageGroup->execute();
        }
    }

    /**
     * @param string $groupName
     *
     * @return int
     */
    private function getGroupID(string $groupName): int
    {
        if (!isset($this->groups[$groupName])) {
            $htmlGroup = htmlspecialchars($groupName);
            $this->selectGroup->bindParam('name', $htmlGroup, PDO::PARAM_STR);
            $this->selectGroup->execute();
            $id = $this->selectGroup->fetchColumn();
            if ($id === false) {
                $this->insertGroup->bindParam('name', $htmlGroup, PDO::PARAM_STR);
                $this->insertGroup->execute();
                $id = $this->database->lastInsertId();
            }
            $this->groups[$groupName] = (int)$id;
        }

        return $this->groups[$groupName];
    }

    /**
     * @param int $packageId
     * @param array $licenses
     */
    private function addPackageToLicenses(int $packageId, array $licenses)
    {
        $this->cleanupPackageLicense->bindParam('package', $packageId, PDO::PARAM_INT);
        $this->cleanupPackageLicense->execute();
        foreach ($licenses as $license) {
            $this->insertPackageLicense->bindParam('package', $packageId, PDO::PARAM_INT);
            $this->insertPackageLicense->bindValue('license', $this->getLicenseID($license), PDO::PARAM_INT);
            $this->insertPackageLicense->execute();
        }
    }

    /**
     * @param string $licenseName
     *
     * @return int
     */
    private function getLicenseID(string $licenseName): int
    {
        if (!isset($this->licenses[$licenseName])) {
            $htmlLicense = htmlspecialchars($licenseName);
            $this->selectLicense->bindParam('name', $htmlLicense, PDO::PARAM_STR);
            $this->selectLicense->execute();
            $id = $this->selectLicense->fetchColumn();
            if ($id === false) {
                $this->insertLicense->bindParam('name', $htmlLicense, PDO::PARAM_STR);
                $this->insertLicense->execute();
                $id = $this->database->lastInsertId();
            }
            $this->licenses[$licenseName] = (int)$id;
        }

        return $this->licenses[$licenseName];
    }

    /**
     * @param array $relations
     * @param int $packageId
     * @param string $type
     */
    private function addRelation(array $relations, int $packageId, string $type)
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

    /**
     * @param string $fileName
     *
     * @return int
     */
    private function getFileIndexID(string $fileName): int
    {
        if (!isset($this->files[$fileName])) {
            $htmlFile = htmlspecialchars($fileName);
            $this->selectFileIndex->bindParam('name', $htmlFile, PDO::PARAM_STR);
            $this->selectFileIndex->execute();
            $id = $this->selectFileIndex->fetchColumn();
            if ($id === false) {
                $this->insertFileIndex->bindParam('name', $htmlFile, PDO::PARAM_STR);
                $this->insertFileIndex->execute();
                $id = $this->database->lastInsertId();
            }
            $this->files[$fileName] = (int)$id;
        }

        return $this->files[$fileName];
    }

    /**
     * @param array $files
     * @param int $packageId
     */
    private function insertFiles(array $files, int $packageId)
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

    /**
     * @param int $repoId
     * @param Package $package
     */
    private function updatePackage(int $repoId, Package $package)
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
            $packageId = $this->database->lastInsertId();
        }

        $packageId = (int)$packageId;

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
        $this->database->query('
            UPDATE
                package_relation
            SET
                dependsId = NULL
            ');

        // Look for depends within the same repo
        $this->database->query('
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
        $this->database->query('
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

    /**
     * @param int $repoId
     * @param int $packageMTime
     * @param array $allPackages
     */
    private function cleanupObsoletePackages(int $repoId, int $packageMTime, array $allPackages)
    {
        $cleanupPackages = $this->database->prepare('
            DELETE FROM
                packages
            WHERE
                id = :packageId
            ');
        $cleanupRelations = $this->database->prepare('
            DELETE FROM
                package_relation
            WHERE
                packageId = :packageId
            ');
        if (Config::get('packages', 'files')) {
            $cleanupFiles = $this->database->prepare('
                DELETE FROM
                    files
                WHERE
                    package = :packageId
                ');
            $cleanupPackageFileIndex = $this->database->prepare('
                DELETE FROM
                    package_file_index
                WHERE
                    package = :packageId
                ');
        }
        $cleanupPackageGroup = $this->database->prepare('
            DELETE FROM
                package_group
            WHERE
                package = :packageId
            ');
        $cleanupPackageLicense = $this->database->prepare('
            DELETE FROM
                package_license
            WHERE
                package = :packageId
            ');
        $repoPackages = $this->database->prepare('
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

    private function cleanupObsoleteRepositories(OutputInterface $output)
    {
        $repos = $this->database->query('
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
                $this->printDebug("\tRemoving repository [$repo[name]] ($repo[arch])", $output);
                $this->cleanupObsoletePackages($repo['id'], time(), array());
                $this->database->query('
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
        $this->database->query('
            DELETE FROM
                groups
            WHERE
                NOT EXISTS (
                    SELECT * FROM package_group WHERE package_group.group = groups.id
                )
            ');
        $this->database->query('
            DELETE FROM
                licenses
            WHERE
                NOT EXISTS (
                    SELECT * FROM package_license WHERE package_license.license = licenses.id
                )
            ');
        $this->database->query('
            DELETE FROM
                packagers
            WHERE
                NOT EXISTS (
                    SELECT * FROM packages WHERE packages.packager = packagers.id
                )
            ');
        $this->database->query('
            DELETE FROM
                architectures
            WHERE
                NOT EXISTS (
                    SELECT * FROM packages WHERE packages.arch = architectures.id
                )
            ');
        if (Config::get('packages', 'files')) {
            $this->database->query('
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
