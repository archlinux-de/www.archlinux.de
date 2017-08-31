<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class NewsAuthor
{
    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $uri;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return NewsAuthor
     */
    public function setName(string $name): NewsAuthor
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return NewsAuthor
     */
    public function setUri(string $uri): NewsAuthor
    {
        $this->uri = $uri;
        return $this;
    }
}
