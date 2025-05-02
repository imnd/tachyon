<?php

namespace tachyon\components;

use tachyon\db\dataMapper\RepositoryInterface;

/**
 * Contains useful functions for working for repository
 *
 * @author imndsu@gmail.com
 */
class RepositoryList
{
    protected string $pkField = 'id';
    protected string $valueField = 'name';
    protected string | bool $emptyVal = '...';

    public function __construct(protected RepositoryInterface $repository) {}

    /**
     * list for select
     */
    public function getAllSelectList(): array
    {
        return $this->getSelectList($this->repository->findAllRaw());
    }

    /**
     * list for select from array of the table rows: $items
     *
     * @param array $rows array the table rows
     */
    public function getSelectList(array $rows): array
    {
        $selectList = [];
        if ($this->getEmptyVal() !== false) {
            $selectList[] = [
                'value' => '',
                'contents' => $this->getEmptyVal(),
            ];
        }
        foreach ($rows as $row) {
            $selectList[] = [
                'value' => $row[$this->getPkField()],
                'contents' => $row[$this->getValueField()],
            ];
        }
        return $selectList;
    }

    /**
     * list for select from array $array
     *
     * @param array   $array
     * @param boolean $keyIndexed indexed with keys or array values
     * @param string  $emptyVal
     */
    public function getSelectListFromArr(
        array $array,
        bool $keyIndexed = false,
        string $emptyVal = '...'
    ): array {
        $items = [];
        foreach ($array as $key => $value) {
            $items[] = [
                $this->getPkField() => $keyIndexed ? $key : $value,
                $this->getValueField() => $value,
            ];
        }
        if (property_exists($this->repository, 'emptyVal')) {
            $this->repository->emptyVal = $emptyVal;
        } else {
            $this->emptyVal = $emptyVal;
        }

        return $this->getSelectList($items);
    }

    # getters

    private function getPkField()
    {
        return $this->repository->pkField ?? $this->pkField;
    }

    private function getValueField()
    {
        return $this->repository->valueField ?? $this->valueField;
    }

    private function getEmptyVal()
    {
        return $this->repository->emptyVal ?? $this->emptyVal;
    }
}
