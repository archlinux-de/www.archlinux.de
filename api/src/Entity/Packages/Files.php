<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Files implements \IteratorAggregate
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 4294967295)]
    private ?string $files;

    private function __construct(?string $files)
    {
        $this->files = $files;
    }

    /**
     * @param string[] $filesArray
     */
    public static function createFromArray(array $filesArray): self
    {
        sort($filesArray);
        return new self(empty($filesArray) ? null : implode("\n", $filesArray));
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
        if (!$this->files) {
            return [];
        }

        return explode("\n", $this->files);
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
