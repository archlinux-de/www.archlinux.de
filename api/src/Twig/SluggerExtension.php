<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Extension\AbstractExtension;

class SluggerExtension extends AbstractExtension
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    #[AsTwigFilter('slug')]
    public function slug(string $string, string $separator = '-', ?string $locale = null): AbstractUnicodeString
    {
        return $this->slugger->slug($string, $separator, $locale);
    }
}
