<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class QueryRequest
{
    /**
     * @var string
     * @Assert\Length(max=191)
     * @Assert\Regex(
     *     pattern="/^[\w@:\.+\- ]+$/u",
     *     normalizer="trim"
     * )
     */
    private $query;

    /**
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
