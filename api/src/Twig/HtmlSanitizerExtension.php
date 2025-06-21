<?php

namespace App\Twig;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlSanitizerExtension extends AbstractExtension
{
    public function __construct(private readonly HtmlSanitizerInterface $htmlSanitizer)
    {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('sanitize', $this->htmlSanitizer->sanitize(...), ['is_safe' => ['html']])
        ];
    }
}
