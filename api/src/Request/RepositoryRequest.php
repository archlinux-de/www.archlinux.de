<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RepositoryRequest
{
    #[Assert\Length(min: 0, max: 25)]
    #[Assert\Regex('/^[a-z\-]+$/')]
    private string $repository;

    public function __construct(string $repository)
    {
        $this->repository = $repository;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }
}
