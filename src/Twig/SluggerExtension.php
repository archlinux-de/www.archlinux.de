<?php

namespace App\Twig;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SluggerExtension extends AbstractExtension
{
    /** @var SluggerInterface */
    private $slugger;

    /**
     * @param SluggerInterface $slugger
     */
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('slug', [$this, 'slug']),
        ];
    }

    /**
     * @param string $string
     * @param string $separator
     * @param string|null $locale
     * @return AbstractUnicodeString
     */
    public function slug(string $string, string $separator = '-', string $locale = null): AbstractUnicodeString
    {
        return $this->slugger->slug($string, $separator, $locale);
    }
}
