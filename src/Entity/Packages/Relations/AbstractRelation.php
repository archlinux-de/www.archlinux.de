<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AbstractRelationRepository")
 * @ORM\InheritanceType(value="SINGLE_TABLE")
 * @ORM\Table(name="packages_relation", indexes={@ORM\Index(columns={"target_name"})})
 */
abstract class AbstractRelation
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $targetName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $targetVersion;

    /**
     * @var Package
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $target;

    /**
     * @var Package
     */
    protected $source;

    /**
     * @param null|string $targetName
     * @param null|string $targetVersion
     */
    public function __construct(?string $targetName, ?string $targetVersion = null)
    {
        $this->targetName = $targetName;
        $this->targetVersion = $targetVersion;
    }

    /**
     * @param string $targetDefinition
     * @return static
     */
    public static function createFromString(string $targetDefinition): self
    {
        if (preg_match('/^([\w-]+?)((?:<|<=|=|>=|>)+[\w\.:]+)/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = $matches[2];
        } elseif (preg_match('/^([\w-]+)/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = null;
        } else {
            $targetName = $targetDefinition;
            $targetVersion = null;
        }
        return new static($targetName, $targetVersion);
    }

    /**
     * @param Package $package
     * @return $this
     */
    public function setSource(Package $package)
    {
        $this->source = $package;
        return $this;
    }

    /**
     * @return Package
     */
    public function getTarget(): ?Package
    {
        return $this->target;
    }

    /**
     * @param Package $target
     */
    public function setTarget(Package $target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getTargetName(): string
    {
        return $this->targetName;
    }

    /**
     * @return string
     */
    public function getTargetVersion(): ?string
    {
        return $this->targetVersion;
    }
}
