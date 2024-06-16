<?php

namespace App\Twig;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SluggerExtension extends AbstractExtension
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
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

    public function slug(string $string, string $separator = '-', string $locale = null): AbstractUnicodeString
    {
        return $this->slugger->slug($string, $separator, $locale);
    }
}
