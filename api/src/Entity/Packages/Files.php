<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FilesRepository")
 */
class Files implements \IteratorAggregate
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @Assert\Length(max="4294967295")
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private string $files;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Packages\Package", mappedBy="files", fetch="LAZY")
     */
    private Package $package;

    private function __construct(string $files)
    {
        $this->files = $files;
    }

    /**
     * @param string[] $filesArray
     */
    public static function createFromArray(array $filesArray): self
    {
        sort($filesArray);
        return new self(implode("\n", $filesArray));
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): Files
    {
        $this->package = $package;
        return $this;
    }

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
