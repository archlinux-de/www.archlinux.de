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
 * @ORM\Table(
 *     indexes={
 *          @ORM\Index(columns={"buildDate"}),
 *          @ORM\Index(columns={"name"})
 *     },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name", "repository_id"})
 *     }
 * )
 */
class Package
{
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
     * @ORM\ManyToOne(targetEntity="Repository", inversedBy="packages", fetch="EAGER")
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
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/")
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/")
     *
     * @ORM\Column(name="base", type="string")
     */
    private $base;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\.\-\+_:~]{1,255}$/")
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
     * @var string|null
     * @Assert\Url(protocols={"http", "https", "ftp"})
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $url;

    /**
     * @var string[]|null
     * @Assert\All({
     *      @Assert\Length(min="3", max="100", allowEmptyString="false")
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
     * @var Packager|null
     * @Assert\Valid()
     *
     * @ORM\Embedded(class="App\Entity\Packages\Packager")
     */
    private $packager;

    /**
     * @var float
     *
     * @ORM\Column(name="popularity", type="float", nullable=false, options={"default": 0})
     */
    private $popularity = 0;

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
    private $replacements;

    /**
     * @var Collection<int, Conflict>
     * @Assert\Valid()
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Conflict",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="LAZY"
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
     *     orphanRemoval=true,
     *     fetch="LAZY"
     * )
     */
    private $provisions;

    /**
     * @var Collection<int, Dependency>
     * @Assert\Valid()
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\Dependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="LAZY"
     * )
     */
    private $dependencies;

    /**
     * @var Collection<int, OptionalDependency>
     * @Assert\Valid()
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\OptionalDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="LAZY"
     * )
     */
    private $optionalDependencies;

    /**
     * @var Collection<int, MakeDependency>
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\MakeDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="LAZY"
     * )
     */
    private $makeDependencies;

    /**
     * @var Collection<int, CheckDependency>
     * @Assert\Valid()
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Packages\Relations\CheckDependency",
     *     mappedBy="source",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="LAZY"
     * )
     */
    private $checkDependencies;

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

        $this->fileName = $name . '-' . $version . '-' . $architecture . '.pkg.tar.xz';

        $this->makeDependencies = new ArrayCollection();
        $this->checkDependencies = new ArrayCollection();
        $this->optionalDependencies = new ArrayCollection();
        $this->conflicts = new ArrayCollection();
        $this->dependencies = new ArrayCollection();
        $this->provisions = new ArrayCollection();
        $this->replacements = new ArrayCollection();
    }

    /**
     * @return float
     */
    public function getPopularity(): float
    {
        return $this->popularity;
    }

    /**
     * @param float $popularity
     * @return Package
     */
    public function setPopularity(float $popularity): Package
    {
        $this->popularity = $popularity;
        return $this;
    }

    /**
     * @param Package $package
     * @return Package
     */
    public function update(Package $package): Package
    {
        $this->setVersion($package->getVersion());
        $this->setArchitecture($package->getArchitecture());

        $this->setFileName($package->getFileName());
        $this->setUrl($package->getUrl());
        $this->setDescription($package->getDescription());
        $this->setBase($package->getBase());
        $this->setBuildDate($package->getBuildDate());
        $this->setCompressedSize($package->getCompressedSize());
        $this->setInstalledSize($package->getInstalledSize());
        $this->setPackager($package->getPackager());
        $this->setSha256sum($package->getSha256sum());
        $this->setPgpSignature($package->getPgpSignature());
        $this->setLicenses($package->getLicenses());
        $this->setGroups($package->getGroups());
        $this->setFiles($package->getFiles());

        $this->getDependencies()->clear();
        foreach ($package->getDependencies() as $depend) {
            $this->addDependency($depend);
        }

        $this->getConflicts()->clear();
        foreach ($package->getConflicts() as $conflict) {
            $this->addConflict($conflict);
        }

        $this->getReplacements()->clear();
        foreach ($package->getReplacements() as $replacement) {
            $this->addReplacement($replacement);
        }

        $this->getOptionalDependencies()->clear();
        foreach ($package->getOptionalDependencies() as $optDepend) {
            $this->addOptionalDependency($optDepend);
        }

        $this->getProvisions()->clear();
        foreach ($package->getProvisions() as $provide) {
            $this->addProvision($provide);
        }

        $this->getMakeDependencies()->clear();
        foreach ($package->getMakeDependencies() as $makeDepend) {
            $this->addMakeDependency($makeDepend);
        }

        $this->getCheckDependencies()->clear();
        foreach ($package->getCheckDependencies() as $checkDepend) {
            $this->addCheckDependency($checkDepend);
        }

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

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return Package
     */
    public function setUrl(?string $url): Package
    {
        $this->url = $url;
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
     * @return Packager|null
     */
    public function getPackager(): ?Packager
    {
        return $this->packager;
    }

    /**
     * @param Packager|null $packager
     * @return Package
     */
    public function setPackager(?Packager $packager): Package
    {
        $this->packager = $packager;
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
        return (string)$this->pgpSignature;
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
     * @return string[]|null
     */
    public function getLicenses(): ?array
    {
        return $this->licenses;
    }

    /**
     * @param string[]|null $licenses
     * @return Package
     */
    public function setLicenses(?array $licenses): Package
    {
        $this->licenses = $licenses;
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
     * @return Collection<int, Dependency>
     */
    public function getDependencies(): Collection
    {
        return $this->dependencies;
    }

    /**
     * @param Dependency $dependency
     * @return Package
     */
    public function addDependency(Dependency $dependency): Package
    {
        $dependency->setSource($this);
        $this->dependencies->add($dependency);
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
        return $this->replacements;
    }

    /**
     * @param Replacement $replacement
     * @return Package
     */
    public function addReplacement(Replacement $replacement): Package
    {
        $replacement->setSource($this);
        $this->replacements->add($replacement);
        return $this;
    }

    /**
     * @return Collection<int, OptionalDependency>
     */
    public function getOptionalDependencies(): Collection
    {
        return $this->optionalDependencies;
    }

    /**
     * @param OptionalDependency $optionalDependency
     * @return Package
     */
    public function addOptionalDependency(OptionalDependency $optionalDependency): Package
    {
        $optionalDependency->setSource($this);
        $this->optionalDependencies->add($optionalDependency);
        return $this;
    }

    /**
     * @return Collection<int, Provision>
     */
    public function getProvisions(): Collection
    {
        return $this->provisions;
    }

    /**
     * @param Provision $provision
     * @return Package
     */
    public function addProvision(Provision $provision): Package
    {
        $provision->setSource($this);
        $this->provisions->add($provision);
        return $this;
    }

    /**
     * @return Collection<int, MakeDependency>
     */
    public function getMakeDependencies(): Collection
    {
        return $this->makeDependencies;
    }

    /**
     * @param MakeDependency $makeDependency
     * @return Package
     */
    public function addMakeDependency(MakeDependency $makeDependency): Package
    {
        $makeDependency->setSource($this);
        $this->makeDependencies->add($makeDependency);
        return $this;
    }

    /**
     * @return Collection<int, CheckDependency>
     */
    public function getCheckDependencies(): Collection
    {
        return $this->checkDependencies;
    }

    /**
     * @param CheckDependency $checkDependency
     * @return Package
     */
    public function addCheckDependency(CheckDependency $checkDependency): Package
    {
        $checkDependency->setSource($this);
        $this->checkDependencies->add($checkDependency);
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Repository
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }
}
