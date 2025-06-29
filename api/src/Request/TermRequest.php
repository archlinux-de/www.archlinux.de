<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class TermRequest
{
    #[Assert\Length(min: 0, max: 50)]
    #[Assert\Regex(
        pattern: '/^[\w\- ]+$/u',
        normalizer: 'trim'
    )]
    private readonly string $term;

    public function __construct(string $term)
    {
        $this->term = $term;
    }

    public function getTerm(): string
    {
        return $this->term;
    }
}
