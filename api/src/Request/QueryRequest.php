<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class QueryRequest
{
    /**
     * @Assert\Length(max=191)
     * @Assert\Regex(
     *     pattern="/^[\w@:\.+\- ]+$/u",
     *     normalizer="trim"
     * )
     */
    private string $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
