<?php

namespace App\Datatables\Request;

use Symfony\Component\Validator\Constraints as Assert;

class Order implements \JsonSerializable
{
    public const ASC = 'asc';
    public const DESC = 'desc';
    /**
     * @var Column
     * @Assert\Valid()
     */
    private $column;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Choice({"asc", "desc"})
     */
    private $dir;

    /**
     * @param Column $column
     * @param string $dir
     */
    public function __construct(Column $column, string $dir)
    {
        $this->column = $column;
        $this->dir = $dir;
    }

    /**
     * @return Column
     */
    public function getColumn(): Column
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'column' => ['id' => $this->column->getId()],
            'dir' => $this->dir
        ];
    }
}
