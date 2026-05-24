<?php

namespace tachyon\db;

class Query
{
    /**
     * Поля для выборки/вставки/обновления
     */
    protected array $fields = [];
    /**
     * Условия для выборки
     */
    protected array $where = [];
    /**
     * Условия для соединения
     */
    protected string $join = '';
    /**
     * Поле группировки
     */
    protected string $groupBy = '';
    /**
     * Поля сортировки
     */
    protected array $orderBy = [];
    /**
     * LIMIT
     */
    protected string $limit = '';

    /**
     * Добавляет условие выборки
     */
    public function addWhere(array $where = null): static
    {
        if (!empty($where)) {
            $this->where = array_merge($this->where, $where);
        }
        return $this;
    }

    /**
     * Устанавливает условие выборки
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
     * Добавляет поля для выборки
     */
    public function addFields(array $fieldNames): static
    {
        $this->fields = array_merge($this->fields, $fieldNames);
        return $this;
    }

    /**
     * Устанавливает поля для выборки
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
     * Устанавливает строку для JOIN
     */
    public function setJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): static
    {
        $this->join = " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Добавляет строку для JOIN
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
     * Добавляет в массив orderBy новый элемент
     */
    public function orderBy(string $fieldName, string $order = 'ASC'): static
    {
        $this->orderBy[$fieldName] = $order;
        return $this;
    }

    /**
     * Устанавливает orderBy
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
     * Возвращает форматированную строку ORDER BY
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
     * Устанавливает форматированную строку LIMIT
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
     * Устанавливает groupBy
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
     * Возвращает форматированную строку GROUP BY
     */
    public function groupByString(): string
    {
        if ($this->groupBy !== '') {
            return " GROUP BY {$this->groupBy} ";
        }
        return '';
    }
}