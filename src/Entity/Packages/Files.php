<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FilesRepository")
 * @phpstan-implements \IteratorAggregate<string>
 */
class Files implements \IteratorAggregate
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
     * @var string
     * @Assert\Length(max="4294967295")
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $files;

    /**
     * @var Package
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Packages\Package", mappedBy="files", fetch="LAZY")
     */
    private $package;

    /**
     * @param string $files
     */
    private function __construct(string $files)
    {
        $this->files = $files;
    }

    /**
     * @param string[] $filesArray
     * @return Files
     */
    public static function createFromArray(array $filesArray): self
    {
        sort($filesArray);
        return new self(implode("\n", $filesArray));
    }

    /**
     * @return Package
     */
    public function getPackage(): Package
    {
        return $this->package;
    }

    /**
     * @param Package $package
     * @return Files
     */
    public function setPackage(Package $package): Files
    {
        $this->package = $package;
        return $this;
    }

    /**
     * @return \Iterator<string>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->getFiles());
    }

    /**
     * @return string[]
     */
    private function getFiles(): array
    {
        if (empty($this->files)) {
            return [];
        }

        return explode("\n", $this->files);
    }
}
