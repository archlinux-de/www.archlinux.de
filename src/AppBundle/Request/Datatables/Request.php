<?php

namespace AppBundle\Request\Datatables;

class Request
{
    /** @var int */
    private $draw;
    /** @var int */
    private $start;
    /** @var int */
    private $length;
    /** @var Search */
    private $search;
    /** @var Order[] */
    private $order;
    /** @var Column[] */
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
     * @param Search $search
     * @return Request
     */
    public function setSearch(Search $search): Request
    {
        $this->search = $search;
        return $this;
    }

    /**
     * @param Order $order
     * @return Request
     */
    public function addOrder(Order $order): Request
    {
        $this->order[] = $order;
        return $this;
    }

    /**
     * @param Column $column
     * @return Request
     */
    public function addColumn(Column $column): Request
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
     * @return Search
     */
    public function getSearch(): Search
    {
        return $this->search;
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->order;
    }

    /**
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
}
