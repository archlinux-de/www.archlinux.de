<?php

namespace App\Entity\Packages;

use App\Repository\FilesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FilesRepository::class)]
class Files implements \IteratorAggregate
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 4294967295)]
    private string $files;

    #[ORM\OneToOne(mappedBy: 'files', targetEntity: Package::class, fetch: 'LAZY')]
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
