<?php

namespace App\Entity\Packages;

use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PackageRepository")
 * @ORM\Table(indexes={@ORM\Index(columns={"buildDate"}), @ORM\Index(columns={"name"})})
 */
class Package implements \JsonSerializable
{
    private const PKGEXT = '.pkg.tar.xz';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Repository
     *
     * @ORM\ManyToOne(targetEntity="Repository", inversedBy="packages")
     */
    private $repository;

    /**
     * @var string
     *
     * @ORM\Column(name="fileName", type="string")
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="base", type="string")
     */
    private $base;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string")
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string")
     */
    private $description = '';

    /**
     * @var string[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $groups;

    /**
     * @var integer
     *
     * @ORM\Column(name="compressedSize", type="bigint")
     */
    private $compressedSize = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="installedSize", type="bigint")
     */
    private $installedSize = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="md5sum", type="string", length=32, nullable=true)
     */
    private $md5sum;

    /**
     * @var string
     *
     * @ORM\Column(name="sha256sum", type="string", length=64, nullable=true)
     */
    private $sha256sum;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="blob", nullable=true)
     */
    private $signature;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $url;

    /**
     * @var string[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $licenses;

    /**
     * @var string
     *
     * @ORM\Column(name="architecture", type="string")
     */
    private $architecture;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="buildDate", type="datetime", nullable=true)
     */
    private $buildDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="mTime", type="datetime", nullable=true)
     */
    private $mTime;

    /**
     * @var Packager
     *
     * @ORM\Embedded(class="App\Entity\Packages\Packager")
     */
    private $packager;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Replacement",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $replaces;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Conflict",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $conflicts;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Provision",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $provides;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Dependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $depends;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\OptionalDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $optdepends;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\MakeDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $makedepends;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\CheckDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $checkdepends;

    /**
     * @param Repository $repository
     * @param string $name
     * @param string $version
     * @param string $architecture
     */
    public function __construct(Repository $repository, string $name, string $version, string $architecture)
    {
        $this->name = $name;
        $this->base = $name;
        $this->repository = $repository;
        $this->version = $version;
        $this->architecture = $architecture;

        $this->base = $name;
        $this->fileName = $name . '-' . $version . '-' . $architecture . self::PKGEXT;

        $this->makedepends = new ArrayCollection();
        $this->checkdepends = new ArrayCollection();
        $this->optdepends = new ArrayCollection();
        $this->conflicts = new ArrayCollection();
        $this->depends = new ArrayCollection();
        $this->provides = new ArrayCollection();
        $this->replaces = new ArrayCollection();
    }

    /**
     * @param Repository $repository
     * @param \App\ArchLinux\Package $databasePackage
     * @return Package
     */
    public static function createFromPackageDatabase(
        Repository $repository,
        \App\ArchLinux\Package $databasePackage
    ): Package {
        $package = new self(
            $repository,
            $databasePackage->getName(),
            $databasePackage->getVersion(),
            $databasePackage->getArch()
        );

        return $package->updateFromPackageDatabase($databasePackage);
    }

    /**
     * @param \App\ArchLinux\Package $databasePackage
     * @return Package
     */
    public function updateFromPackageDatabase(\App\ArchLinux\Package $databasePackage): Package
    {
        $this->setName($databasePackage->getName());
        $this->setVersion($databasePackage->getVersion());
        $this->setArchitecture($databasePackage->getArch());

        $this->setFileName($databasePackage->getFileName());
        $this->setUrl($databasePackage->getURL());
        $this->setDescription($databasePackage->getDescription());
        $this->setBase($databasePackage->getBase());
        $this->setBuildDate($databasePackage->getBuildDate());
        $this->setCompressedSize($databasePackage->getCompressedSize());
        $this->setInstalledSize($databasePackage->getInstalledSize());
        $this->setMd5sum($databasePackage->getMD5SUM());
        $this->setPackager(Packager::createFromString($databasePackage->getPackager()));
        $this->setSha256sum($databasePackage->getSHA256SUM());
        $this->setSignature($databasePackage->getPGPSignature());
        $this->setMTime($databasePackage->getMTime());
        $this->setLicenses($databasePackage->getLicenses());
        $this->setGroups($databasePackage->getGroups());

        $this->getDependencies()->clear();
        foreach ($databasePackage->getDepends() as $depend) {
            $this->addDependency(Dependency::createFromString($depend));
        }

        $this->getConflicts()->clear();
        foreach ($databasePackage->getConflicts() as $conflict) {
            $this->addConflict(Conflict::createFromString($conflict));
        }

        $this->getReplacements()->clear();
        foreach ($databasePackage->getReplaces() as $replacement) {
            $this->addReplacement(Replacement::createFromString($replacement));
        }

        $this->getOptionalDependencies()->clear();
        foreach ($databasePackage->getOptDepends() as $optDepend) {
            $this->addOptionalDependency(OptionalDependency::createFromString($optDepend));
        }

        $this->getProvisions()->clear();
        foreach ($databasePackage->getProvides() as $provide) {
            $this->addProvision(Provision::createFromString($provide));
        }

        $this->getMakeDependencies()->clear();
        foreach ($databasePackage->getMakeDepends() as $makeDepend) {
            $this->addMakeDependency(MakeDependency::createFromString($makeDepend));
        }

        $this->getCheckDependencies()->clear();
        foreach ($databasePackage->getCheckDepends() as $checkDepend) {
            $this->addCheckDependency(CheckDependency::createFromString($checkDepend));
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDependencies(): Collection
    {
        return $this->depends;
    }

    /**
     * @param Dependency $dependency
     */
    public function addDependency(Dependency $dependency)
    {
        $dependency->setSource($this);
        $this->depends->add($dependency);
    }

    /**
     * @return Collection
     */
    public function getConflicts(): Collection
    {
        return $this->conflicts;
    }

    /**
     * @param Conflict $conflict
     */
    public function addConflict(Conflict $conflict)
    {
        $conflict->setSource($this);
        $this->conflicts->add($conflict);
    }

    /**
     * @return Collection
     */
    public function getReplacements(): Collection
    {
        return $this->replaces;
    }

    /**
     * @param Replacement $replacement
     */
    public function addReplacement(Replacement $replacement)
    {
        $replacement->setSource($this);
        $this->replaces->add($replacement);
    }

    /**
     * @return Collection
     */
    public function getOptionalDependencies(): Collection
    {
        return $this->optdepends;
    }

    /**
     * @param OptionalDependency $optionalDependency
     */
    public function addOptionalDependency(OptionalDependency $optionalDependency)
    {
        $optionalDependency->setSource($this);
        $this->optdepends->add($optionalDependency);
    }

    /**
     * @return Collection
     */
    public function getProvisions(): Collection
    {
        return $this->provides;
    }

    /**
     * @param Provision $provision
     */
    public function addProvision(Provision $provision)
    {
        $provision->setSource($this);
        $this->provides->add($provision);
    }

    /**
     * @return Collection
     */
    public function getMakeDependencies(): Collection
    {
        return $this->makedepends;
    }

    /**
     * @param MakeDependency $makeDependency
     */
    public function addMakeDependency(MakeDependency $makeDependency)
    {
        $makeDependency->setSource($this);
        $this->makedepends->add($makeDependency);
    }

    /**
     * @return Collection
     */
    public function getCheckDependencies(): Collection
    {
        return $this->checkdepends;
    }

    /**
     * @param CheckDependency $checkDependency
     */
    public function addCheckDependency(CheckDependency $checkDependency)
    {
        $checkDependency->setSource($this);
        $this->checkdepends->add($checkDependency);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getMTime(): ?\DateTime
    {
        return $this->mTime;
    }

    /**
     * @param \DateTime $mTime
     */
    public function setMTime(\DateTime $mTime)
    {
        $this->mTime = $mTime;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @param string $base
     * @return Package
     */
    public function setBase(string $base): Package
    {
        $this->base = $base;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return int
     */
    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    /**
     * @param int $compressedSize
     * @return Package
     */
    public function setCompressedSize(int $compressedSize): Package
    {
        $this->compressedSize = $compressedSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getInstalledSize(): int
    {
        return $this->installedSize;
    }

    /**
     * @param int $installedSize
     * @return Package
     */
    public function setInstalledSize(int $installedSize): Package
    {
        $this->installedSize = $installedSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getMd5sum(): string
    {
        return $this->md5sum;
    }

    /**
     * @param string $md5sum
     * @return Package
     */
    public function setMd5sum(string $md5sum): Package
    {
        $this->md5sum = $md5sum;
        return $this;
    }

    /**
     * @return string
     */
    public function getSha256sum(): string
    {
        return $this->sha256sum;
    }

    /**
     * @param string|null $sha256sum
     * @return Package
     */
    public function setSha256sum(?string $sha256sum): Package
    {
        $this->sha256sum = $sha256sum;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string|null $signature
     * @return Package
     */
    public function setSignature(?string $signature): Package
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Package
     */
    public function setUrl(string $url): Package
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getLicenses(): ?array
    {
        return $this->licenses;
    }

    /**
     * @param string[] $licenses
     */
    public function setLicenses(array $licenses)
    {
        $this->licenses = $licenses;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'repository' => $this->getRepository(),
            'architecture' => $this->getArchitecture(),
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'builddate' => !is_null($this->getBuildDate())
                ? $this->getBuildDate()->format(\DateTime::RFC2822)
                : null,
        ];
    }

    /**
     * @return Repository
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * @param Repository $repository
     * @return Package
     */
    public function setRepository(Repository $repository): Package
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return string
     */
    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    /**
     * @param string $architecture
     * @return Package
     */
    public function setArchitecture(string $architecture): Package
    {
        $this->architecture = $architecture;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Package
     */
    public function setName(string $name): Package
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return Package
     */
    public function setVersion(string $version): Package
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Package
     */
    public function setDescription(string $description): Package
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getBuildDate(): ?\DateTime
    {
        return $this->buildDate;
    }

    /**
     * @param \DateTime|null $buildDate
     * @return Package
     */
    public function setBuildDate(?\DateTime $buildDate): Package
    {
        $this->buildDate = $buildDate;
        return $this;
    }

    /**
     * @return Packager
     */
    public function getPackager(): Packager
    {
        return $this->packager;
    }

    /**
     * @param Packager $packager
     * @return Package
     */
    public function setPackager(Packager $packager): Package
    {
        $this->packager = $packager;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getRepository() . '/' . $this->getFileName();
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return Package
     */
    public function setFileName(string $fileName): Package
    {
        $this->fileName = $fileName;
        return $this;
    }
}
