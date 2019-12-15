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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PackageRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"buildDate"}),
 *     @ORM\Index(columns={"name"})
 * })
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
     * @Assert\Valid()
     *
     * @ORM\ManyToOne(targetEntity="Repository", inversedBy="packages")
     */
    private $repository;

    /**
     * @var string
     * @Assert\Regex("/^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9@\.\-\+_:]{1,255}$/")
     *
     * @ORM\Column(name="fileName", type="string")
     */
    private $fileName;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{,255}$/")
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{,255}$/")
     *
     * @ORM\Column(name="base", type="string")
     */
    private $base;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\.\-\+_]{1,255}$/")
     *
     * @ORM\Column(name="version", type="string")
     */
    private $version;

    /**
     * @var string
     * @Assert\Length(max="255")
     *
     * @ORM\Column(name="description", type="string")
     */
    private $description = '';

    /**
     * @var string[]
     * @Assert\All({
     *      @Assert\Length(min="2", max="100", allowEmptyString="false")
     * })
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $groups = [];

    /**
     * @var integer
     * @Assert\Range(min="0", max="10737418240")
     *
     * @ORM\Column(name="compressedSize", type="bigint")
     */
    private $compressedSize = 0;

    /**
     * @var integer
     * @Assert\Range(min="0", max="10737418240")
     *
     * @ORM\Column(name="installedSize", type="bigint")
     */
    private $installedSize = 0;

    /**
     * @var string
     * @Assert\Regex("/^[0-9a-f]{32}$/")
     *
     * @ORM\Column(name="md5sum", type="string", length=32, nullable=true)
     */
    private $md5sum;

    /**
     * @var string|null
     * @Assert\Regex("/^[0-9a-f]{64}$/")
     *
     * @ORM\Column(name="sha256sum", type="string", length=64, nullable=true)
     */
    private $sha256sum;

    /**
     * @var string|null
     * @Assert\Length(min="50", max="2048", allowEmptyString="true")
     *
     * @ORM\Column(name="pgp_signature", type="blob", nullable=true)
     */
    private $pgpSignature;

    /**
     * @var string
     * @Assert\Url()
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $url;

    /**
     * @var string[]
     * @Assert\All({
     *      @Assert\Length(min="3", max="50", allowEmptyString="false")
     * })
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $licenses;

    /**
     * @var string
     * @Assert\Choice({"x86_64", "any"})
     *
     * @ORM\Column(name="architecture", type="string")
     */
    private $architecture;

    /**
     * @var \DateTime|null
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
     * @Assert\Valid()
     *
     * @ORM\Embedded(class="App\Entity\Packages\Packager")
     */
    private $packager;

    /**
     * @var Collection<int, Replacement>
     * @Assert\Valid()
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
     * @var Collection<int, Conflict>
     * @Assert\Valid()
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
     * @var Collection<int, Provision>
     * @Assert\Valid()
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
     * @var Collection<int, Dependency>
     * @Assert\Valid()
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
     * @var Collection<int, OptionalDependency>
     * @Assert\Valid()
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
     * @var Collection<int, MakeDependency>
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
     * @var Collection<int, CheckDependency>
     * @Assert\Valid()
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
     * @var Files
     * @Assert\Valid()
     *
     * @ORM\OneToOne(
     *     targetEntity="App\Entity\Packages\Files",
     *     cascade={"remove", "persist"},
     *     fetch="LAZY",
     *     inversedBy="package",
     *     orphanRemoval=true
     * )
     */
    private $files;

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
            $databasePackage->getArchitecture()
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
        $this->setArchitecture($databasePackage->getArchitecture());

        $this->setFileName($databasePackage->getFileName());
        $this->setUrl($databasePackage->getUrl());
        $this->setDescription($databasePackage->getDescription());
        $this->setBase($databasePackage->getBase());
        $this->setBuildDate($databasePackage->getBuildDate());
        $this->setCompressedSize($databasePackage->getCompressedSize());
        $this->setInstalledSize($databasePackage->getInstalledSize());
        $this->setMd5sum($databasePackage->getMd5sum());
        $this->setPackager(Packager::createFromString($databasePackage->getPackager()));
        $this->setSha256sum($databasePackage->getSha256sum());
        $this->setPgpSignature($databasePackage->getPgpSignature());
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

        $this->setFiles(Files::createFromArray($databasePackage->getFiles()));

        return $this;
    }

    /**
     * @return Collection<int, Dependency>
     */
    public function getDependencies(): Collection
    {
        return $this->depends;
    }

    /**
     * @param Dependency $dependency
     * @return Package
     */
    public function addDependency(Dependency $dependency): Package
    {
        $dependency->setSource($this);
        $this->depends->add($dependency);
        return $this;
    }

    /**
     * @return Collection<int, Conflict>
     */
    public function getConflicts(): Collection
    {
        return $this->conflicts;
    }

    /**
     * @param Conflict $conflict
     * @return Package
     */
    public function addConflict(Conflict $conflict): Package
    {
        $conflict->setSource($this);
        $this->conflicts->add($conflict);
        return $this;
    }

    /**
     * @return Collection<int, Replacement>
     */
    public function getReplacements(): Collection
    {
        return $this->replaces;
    }

    /**
     * @param Replacement $replacement
     * @return Package
     */
    public function addReplacement(Replacement $replacement): Package
    {
        $replacement->setSource($this);
        $this->replaces->add($replacement);
        return $this;
    }

    /**
     * @return Collection<int, OptionalDependency>
     */
    public function getOptionalDependencies(): Collection
    {
        return $this->optdepends;
    }

    /**
     * @param OptionalDependency $optionalDependency
     * @return Package
     */
    public function addOptionalDependency(OptionalDependency $optionalDependency): Package
    {
        $optionalDependency->setSource($this);
        $this->optdepends->add($optionalDependency);
        return $this;
    }

    /**
     * @return Collection<int, Provision>
     */
    public function getProvisions(): Collection
    {
        return $this->provides;
    }

    /**
     * @param Provision $provision
     * @return Package
     */
    public function addProvision(Provision $provision): Package
    {
        $provision->setSource($this);
        $this->provides->add($provision);
        return $this;
    }

    /**
     * @return Collection<int, MakeDependency>
     */
    public function getMakeDependencies(): Collection
    {
        return $this->makedepends;
    }

    /**
     * @param MakeDependency $makeDependency
     * @return Package
     */
    public function addMakeDependency(MakeDependency $makeDependency): Package
    {
        $makeDependency->setSource($this);
        $this->makedepends->add($makeDependency);
        return $this;
    }

    /**
     * @return Collection<int, CheckDependency>
     */
    public function getCheckDependencies(): Collection
    {
        return $this->checkdepends;
    }

    /**
     * @param CheckDependency $checkDependency
     * @return Package
     */
    public function addCheckDependency(CheckDependency $checkDependency): Package
    {
        $checkDependency->setSource($this);
        $this->checkdepends->add($checkDependency);
        return $this;
    }

    /**
     * @return Files
     */
    public function getFiles(): Files
    {
        return $this->files;
    }

    /**
     * @param Files $files
     * @return Package
     */
    public function setFiles(Files $files): Package
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime|null
     */
    public function getMTime(): ?\DateTime
    {
        return $this->mTime;
    }

    /**
     * @param \DateTime $mTime
     * @return Package
     */
    public function setMTime(\DateTime $mTime): Package
    {
        $this->mTime = $mTime;
        return $this;
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
     * @return string|null
     */
    public function getSha256sum(): ?string
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
     * @return string|null
     */
    public function getPgpSignature(): ?string
    {
        return $this->pgpSignature;
    }

    /**
     * @param string|null $pgpSignature
     * @return Package
     */
    public function setPgpSignature(?string $pgpSignature): Package
    {
        $this->pgpSignature = $pgpSignature;
        return $this;
    }

    /**
     * @return string|null
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
     * @return string[]|null
     */
    public function getLicenses(): ?array
    {
        return $this->licenses;
    }

    /**
     * @param string[] $licenses
     * @return Package
     */
    public function setLicenses(array $licenses): Package
    {
        $this->licenses = $licenses;
        return $this;
    }

    /**
     * @return array<string|null|string[]|Repository>
     */
    public function jsonSerialize(): array
    {
        return [
            'repository' => $this->getRepository(),
            'architecture' => $this->getArchitecture(),
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'builddate' => $this->getBuildDate() !== null
                ? $this->getBuildDate()->format(\DateTime::RFC2822)
                : null,
            'groups' => $this->getGroups()
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
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param string[] $groups
     * @return Package
     */
    public function setGroups(array $groups): Package
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * @return Packager|null
     */
    public function getPackager(): ?Packager
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
