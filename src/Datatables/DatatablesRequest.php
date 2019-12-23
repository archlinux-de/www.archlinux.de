<?php

namespace App\Datatables;

use App\Datatables\Request\Column;
use App\Datatables\Request\Order;
use App\Datatables\Request\Search;
use Symfony\Component\Validator\Constraints as Assert;

class DatatablesRequest implements \JsonSerializable
{
    /**
     * @var int
     * @Assert\GreaterThanOrEqual(1)
     */
    private $draw;

    /**
     * @var int
     * @Assert\GreaterThanOrEqual(0)
     */
    private $start;

    /**
     * @var int
     * @Assert\GreaterThanOrEqual(1)
     * @Assert\LessThanOrEqual(100)
     */
    private $length;

    /**
     * @var Search|null
     * @Assert\Valid()
     */
    private $search;

    /**
     * @var Order[]
     * @Assert\Valid()
     */
    private $order = [];

    /**
     * @var Column[]
     * @Assert\Valid()
     */
    private $columns = [];

    /**
     * @param int $draw
     * @param int $start
     * @param int $length
     */
    public function __construct(int $draw, int $start, int $length)
    {
        $this->draw = $draw;
        $this->start = $start;
        $this->length = $length;
    }

    /**
     * @param Order $order
     * @return DatatablesRequest
     */
    public function addOrder(Order $order): DatatablesRequest
    {
        $this->order[] = $order;
        return $this;
    }

    /**
     * @param Column $column
     * @return DatatablesRequest
     */
    public function addColumn(Column $column): DatatablesRequest
    {
        $this->columns[$column->getId()] = $column;
        return $this;
    }

    /**
     * @return int
     */
    public function getDraw(): int
    {
        return $this->draw;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return Search|null
     */
    public function getSearch(): ?Search
    {
        return $this->search;
    }

    /**
     * @param Search $search
     * @return DatatablesRequest
     */
    public function setSearch(Search $search): DatatablesRequest
    {
        $this->search = $search;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search->isValid();
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->order;
    }

    /**
     * @param int $id
     * @return Column
     */
    public function getColumn(int $id): Column
    {
        return $this->columns[$id];
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start,
            'length' => $this->length,
            'search' => $this->search,
            'order' => $this->order,
            'columns' => $this->columns
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return sha1((string)json_encode($this));
    }
}
