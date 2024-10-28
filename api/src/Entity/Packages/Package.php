<?php

namespace App\Entity\Packages;

use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Repository\PackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\Index(columns: ['build_date'])]
#[ORM\Index(columns: ['name'])]
#[ORM\UniqueConstraint(columns: ['name', 'repository_id'])]
class Package
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Repository::class, fetch: 'EAGER', inversedBy: 'packages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid]
    private Repository $repository;

    #[ORM\Column]
    #[Assert\Regex('/^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9@\.\-\+_:]{1,255}$/')]
    private string $fileName;

    #[ORM\Column]
    #[Assert\Regex('/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/')]
    private string $name;

    #[ORM\Column]
    #[Assert\Regex('/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/')]
    private string $base;

    #[ORM\Column]
    #[Assert\Regex('/^[a-zA-Z0-9@\.\-\+_:~]{1,255}$/')]
    private string $version;

    #[ORM\Column]
    #[Assert\Length(max: 255)]
    private string $description = '';

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Assert\All(
        [new Assert\Length(min: 2, max: 100)]
    )]
    private ?array $groups = [];

    #[ORM\Column(type: Types::BIGINT)]
    #[Assert\Range(min: 0, max: 107374182400)]
    private int $compressedSize = 0;

    #[ORM\Column(type: Types::BIGINT)]
    #[Assert\Range(min: 0, max: 107374182400)]
    private int $installedSize = 0;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\Regex('/^[0-9a-f]{64}$/')]
    private ?string $sha256sum = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $url = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Assert\All(
        [new Assert\Length(min: 3, max: 100)]
    )]
    private ?array $licenses = null;

    #[ORM\Column]
    #[Assert\Choice(['x86_64', 'any'])]
    private string $architecture;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $buildDate = null;

    #[ORM\Embedded(class: Packager::class)]
    #[Assert\Valid]
    private ?Packager $packager = null;

    #[ORM\Embedded(class: Popularity::class)]
    #[Assert\Valid]
    private ?Popularity $popularity = null;

    /**
     * @var Collection<int, Replacement>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: Replacement::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $replacements;

    /**
     * @var Collection<int, Conflict>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: Conflict::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $conflicts;

    /**
     * @var Collection<int, Provision>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: Provision::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $provisions;

    /**
     * @var Collection<int, Dependency>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: Dependency::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $dependencies;

    /**
     * @var Collection<int, OptionalDependency>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: OptionalDependency::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $optionalDependencies;

    /**
     * @var Collection<int, MakeDependency>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: MakeDependency::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $makeDependencies;

    /**
     * @var Collection<int, CheckDependency>
     */
    #[ORM\OneToMany(
        mappedBy: 'source',
        targetEntity: CheckDependency::class,
        cascade: ['persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $checkDependencies;

    #[ORM\OneToOne(
        targetEntity: Files::class,
        cascade: ['remove', 'persist'],
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid]
    private Files $files;

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

        $this->files = Files::createFromArray([]);
    }

    public function getPopularity(): ?Popularity
    {
        return $this->popularity;
    }

    public function setPopularity(?Popularity $popularity): Package
    {
        $this->popularity = $popularity;
        return $this;
    }

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

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): Package
    {
        $this->version = $version;
        return $this;
    }

    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    public function setArchitecture(string $architecture): Package
    {
        $this->architecture = $architecture;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): Package
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): Package
    {
        $this->url = $url;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Package
    {
        $this->description = $description;
        return $this;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function setBase(string $base): Package
    {
        $this->base = $base;
        return $this;
    }

    public function getBuildDate(): ?\DateTime
    {
        return $this->buildDate;
    }

    public function setBuildDate(?\DateTime $buildDate): Package
    {
        $this->buildDate = $buildDate;
        return $this;
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    public function setCompressedSize(int $compressedSize): Package
    {
        $this->compressedSize = $compressedSize;
        return $this;
    }

    public function getInstalledSize(): int
    {
        return $this->installedSize;
    }

    public function setInstalledSize(int $installedSize): Package
    {
        $this->installedSize = $installedSize;
        return $this;
    }

    public function getPackager(): ?Packager
    {
        return $this->packager;
    }

    public function setPackager(?Packager $packager): Package
    {
        $this->packager = $packager;
        return $this;
    }

    public function getSha256sum(): ?string
    {
        return $this->sha256sum;
    }

    public function setSha256sum(?string $sha256sum): Package
    {
        $this->sha256sum = $sha256sum;
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
        return $this->groups ?? [];
    }

    /**
     * @param string[] $groups
     * @return Package
     */
    public function setGroups(array $groups): Package
    {
        $this->groups = empty($groups) ? null : $groups;
        return $this;
    }

    public function getFiles(): Files
    {
        return $this->files;
    }

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

    public function addCheckDependency(CheckDependency $checkDependency): Package
    {
        $checkDependency->setSource($this);
        $this->checkDependencies->add($checkDependency);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }
}
