<?php

namespace App\Twig;

use HTMLPurifier;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PurifierExtension extends AbstractExtension
{
    public function __construct(private HTMLPurifier $purifier)
    {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('purify', [$this->purifier, 'purify'], ['is_safe' => ['html']])
        ];
    }
}
