<?php

namespace App\Dto;

readonly class AppStreamDataComponentDto
{
    public function __construct(
        private string $packageName,
        /** @var string[]|null */
        private ?array $categories = null,
        /** @var string[]|null */
        private ?array $keywords = null,
        private ?string $description = null
    ) {
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @return string[]|null
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    /**
     * @return string[]|null
     */
    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
