<?php

namespace tachyon\db\dataMapper;

/**
 * @author imndsu@gmail.com
 */
interface EntityInterface
{
    public function fromState(array $state): Entity;

    public function setAttributes(array $state);

    public function getAttributes(): array;

    /**
     * Label for entity field
     *
     * @param string $attribute entity name
     *
     * @return string
     */
    public function getCaption(string $attribute): string;

    /**
     * Extracting value of attribute $attribute
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function getAttribute(string $attribute);

    /**
     * Assigning value $value to attribute $attribute
     *
     * @param mixed $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value = null);

    /**
     * Primary key field name
     *
     * @return string
     */
    public function getPkName(): string;

    /**
     * Primary key value
     *
     * @return mixed
     */
    public function getPk();
}
