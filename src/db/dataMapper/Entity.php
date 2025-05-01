<?php

namespace tachyon\db\dataMapper;

use tachyon\exceptions\ValidationException;
use tachyon\helpers\{
    ClassHelper, StringHelper
};
use tachyon\components\validation\{
    ValidationInterface, Validator
};

abstract class Entity implements EntityInterface, UnitOfWorkInterface, ValidationInterface
{
    /**
     * fetched from the database or a newly created entity
     */
    protected bool $isNew = true;
    /**
     * the name of the database table
     */
    protected string $tableName = '';

    /**
     * captions for entity fields
     */
    protected array $attributeCaptions = [];
    /**
     * the name of the primary key field
     */
    protected mixed $pk = 'id';
    /**
     * validation errors
     */
    protected array $errors = [];

    public function __construct(DbContext $dbContext, Validator $validator)
    {
        $this->dbContext = $dbContext;
        $this->validator = $validator;
        if (empty($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', ClassHelper::getClassName($this));
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr)) . 's';
        }
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * the caption for fields of the entity
     */
    public function getCaption(string $attribute): string
    {
        return $this->attributeCaptions[$attribute] ?? $this->attributeCaptions[StringHelper::snakeToCamel($attribute)] ?? $attribute;
    }

    /**
     * Извлечение значения аттрибута $attribute
     */
    public function getAttribute(string $attribute)
    {
        $method = 'get' . ucfirst(StringHelper::snakeToCamel($attribute));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }

    /**
     * the assignment of the value $value to the attribute $attribute
     * the entity is not marked as modified
     */
    public function setAttribute(mixed $attribute, mixed $value = null): void
    {
        if (is_array($attribute)) {
            $value = current($attribute);
            $attribute = key($attribute);
        }
        $this->$attribute = $value;
    }

    public function setIsNew(bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    protected function _setAttribute(string $attribute, string $value = null): self
    {
        if (!is_null($value)) {
            $this->$attribute = $value;
            if (!$this->isNew) {
                $this->markDirty();
            }
        }
        return $this;
    }

    /**
     * the primary key field name
     */
    public function getPkName(): string
    {
        return $this->pk;
    }

    /**
     * the primary key value
     */
    public function getPk(): mixed
    {
        return $this->getAttribute($this->pk);
    }

    /**
     * setting the primary key value
     */
    public function setPk(mixed $pk): void
    {
        $this->{$this->pk} = $pk;
    }

    # region Unit of work

    protected DbContext $dbContext;

    public function getDbContext(): DbContext
    {
        return $this->dbContext;
    }

    public function isNew(): bool
    {
        return $this->dbContext->isNew($this);
    }

    public function isDirty(): bool
    {
        return $this->dbContext->isDirty($this);
    }

    public function isDeleted(): bool
    {
        return $this->dbContext->isDeleted($this);
    }

    /**
     * mark the newly created entity as new
     */
    public function markNew(): self
    {
        $this->dbContext->registerNew($this);
        return $this;
    }

    /**
     * mark the entity as modified
     */
    public function markDirty(): self
    {
        $this->dbContext->registerDirty($this);
        return $this;
    }

    /**
     * marks the entity for deletion
     */
    public function markDeleted(): self
    {
        $this->dbContext->registerDeleted($this);
        return $this;
    }

    # endregion

    # region Validation

    protected Validator $validator;

    /**
     * returns a list of validation rules
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * entity fields validation
     *
     * @param array|null $attributes array of the fields
     *
     * @throws ValidationException
     */
    public function validate(array $attributes = null): bool
    {
        $this->errors = $this->validator->validate($this, $attributes);
        return empty($this->errors);
    }

    public function getRules(string $fieldName): array
    {
        return $this->validator->getRules($this, $fieldName);
    }

    /**
     * add an error to the error list
     */
    public function addError(string $fieldName, string $message): void
    {
        $this->validator->addError($fieldName, $message);
    }

    /**
     * errors message
     */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }

    /**
     * error message
     */
    public function getErrorsSummary(): string
    {
        return $this->validator->getErrorsSummary($this);
    }

    # endregion
}
