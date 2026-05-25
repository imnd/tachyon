<?php

namespace tachyon\db;

class Query
{
    /**
     * Fields for selection/insertion/update
     */
    protected array $fields = [];
    /**
     * Conditions for selection
     */
    protected array $where = [];
    /**
     * Conditions for join
     */
    protected string $join = '';
    /**
     * Grouping field
     */
    protected string $groupBy = '';
    /**
     * Sorting fields
     */
    protected array $orderBy = [];
    /**
     * LIMIT
     */
    protected string $limit = '';

    /**
     * Adds selection condition
     */
    public function addWhere(array $where = null): static
    {
        if (!empty($where)) {
            $this->where = array_merge($this->where, $where);
        }
        return $this;
    }

    /**
     * Sets selection condition
     */
    public function setWhere(array $where): static
    {
        $this->where = $where;
        return $this;
    }

    public function getWhere(): array
    {
        return $this->where;
    }

    public function clearWhere(): static
    {
        $this->where = [];
        return $this;
    }


    /**
     * Adds fields for selection
     */
    public function addFields(array $fieldNames): static
    {
        $this->fields = array_merge($this->fields, $fieldNames);
        return $this;
    }

    /**
     * Sets fields for selection
     */
    public function setFields(array $fieldNames): static
    {
        $this->fields = $fieldNames;
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function clearFields(): static
    {
        $this->fields = [];
        return $this;
    }

    /**
     * Sets string for JOIN
     */
    public function setJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): static
    {
        $this->join = " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Adds string for JOIN
     */
    public function addJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): static
    {
        $this->join .= " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    public function getJoin(): string
    {
        return $this->join;
    }

    public function clearJoin(): static
    {
        $this->join = '';
        return $this;
    }

    /**
     * Adds new element to orderBy array
     */
    public function orderBy(string $fieldName, string $order = 'ASC'): static
    {
        $this->orderBy[$fieldName] = $order;
        return $this;
    }

    /**
     * Sets orderBy
     */
    public function setOrderBy($orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function clearOrderBy(): static
    {
        $this->orderBy = [];
        return $this;
    }

    /**
     * Returns formatted ORDER BY string
     */
    public function orderByString(): string
    {
        if (count($this->orderBy) === 0) {
            return '';
        }
        $orderBy = [];
        foreach ($this->orderBy as $fieldName => $order) {
            $orderBy[] = "$fieldName $order";
        }

        return ' ORDER BY ' . implode(',', $orderBy);
    }

    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * Sets formatted LIMIT string
     */
    public function setLimit(int $limit, int $offset = null): static
    {
        $this->limit = $limit;

        if (!is_null($offset)) {
            $this->limit = " $offset, {$this->limit}";
        }
        $this->limit = " LIMIT {$this->limit} ";

        return $this;
    }

    public function clearLimit(): static
    {
        $this->limit = '';
        return $this;
    }

    /**
     * Sets groupBy
     */
    public function setGroupBy(string $fieldName): static
    {
        $this->groupBy = $fieldName;
        return $this;
    }

    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    public function clearGroupBy(): static
    {
        $this->groupBy = '';
        return $this;
    }

    /**
     * Returns formatted GROUP BY string
     */
    public function groupByString(): string
    {
        if ($this->groupBy !== '') {
            return " GROUP BY {$this->groupBy} ";
        }
        return '';
    }
}