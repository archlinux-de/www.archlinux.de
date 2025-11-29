<?php

namespace App\Request;

use App\Entity\Packages\Architecture;
use Symfony\Component\Validator\Constraints as Assert;

class ArchitectureRequest
{
    #[Assert\Choice(choices: [Architecture::X86_64, Architecture::I686])]
    private readonly string $architecture;

    public function __construct(string $architecture)
    {
        $this->architecture = $architecture;
    }

    public function getArchitecture(): string
    {
        return $this->architecture;
    }
}
