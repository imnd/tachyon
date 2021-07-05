<?php

namespace tachyon\db\dataMapper;

use tachyon\exceptions\ValidationException;
use tachyon\components\validation\{
    ValidationInterface, Validator
};
use tachyon\traits\ClassName;

abstract class Entity implements EntityInterface, UnitOfWorkInterface, ValidationInterface
{
    use ClassName;

    /**
     * Извлеченная из БД или вновь созданная сущность
     *
     * @var boolean
     */
    protected bool $isNew = true;
    /**
     * Имя таблицы БД
     *
     * @var string
     */
    protected string $tableName = '';

    /**
     * Подписи для поля сущностей
     *
     * @var array
     */
    protected array $attributeCaptions = [];
    /**
     * Имя поля первичного ключа
     *
     * @var mixed
     */
    protected $pk = 'id';
    /**
     * Ошибки валидации
     *
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * @param DbContext $dbContext
     * @param Validator $validator
     */
    public function __construct(DbContext $dbContext, Validator $validator)
    {
        $this->dbContext = $dbContext;
        $this->validator = $validator;
        if (empty($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', $this->getClassName());
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr)) . 's';
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Подпись для поля сущности
     *
     * @param string $attribute имя сущности
     *
     * @return string
     */
    public function getCaption(string $attribute): string
    {
        return $this->attributeCaptions[$attribute] ?? $this->attributeCaptions[$this->getAttrName($attribute)] ?? $attribute;
    }

    /**
     * Извлечение значения аттрибута $attribute
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function getAttribute(string $attribute)
    {
        $method = 'get' . ucfirst($this->getAttrName($attribute));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }

    /**
     * Переводит из snake_case в camelCase
     *
     * @param string $attribute
     * @return string
     */
    private function getAttrName(string $attribute): string
    {
        $arr = array_map(
            static fn($elem) => ucfirst($elem),
            explode('_', $attribute)
        );
        return lcfirst(implode('', $arr));
    }

    /**
     * Присваивание значения $value аттрибуту $attribute
     * При этом сущность не помечается как измененная.
     *
     * @param mixed $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value = null): void
    {
        if (is_array($attribute)) {
            $value = current($attribute);
            $attribute = key($attribute);
        }
        $this->$attribute = $value;
    }

    /**
     * @param bool $isNew
     */
    public function setIsNew(bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    /**
     * @param string      $attribute
     * @param string|null $value
     *
     * @return $this
     */
    protected function _setAttribute(string $attribute, string $value = null): Entity
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
     * Имя поля первичного ключа
     *
     * @return string
     */
    public function getPkName(): string
    {
        return $this->pk;
    }

    /**
     * Значение первичного ключа
     *
     * @return mixed
     */
    public function getPk()
    {
        return $this->getAttribute($this->pk);
    }

    /**
     * Установка значения первичного ключа
     *
     * @param mixed $pk
     *
     * @return mixed
     */
    public function setPk($pk)
    {
        $this->{$this->pk} = $pk;
    }

    # region Unit of work

    /**
     * @var DbContext
     */
    protected DbContext $dbContext;

    /**
     * @return DbContext
     */
    public function getDbContext(): DbContext
    {
        return $this->dbContext;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->dbContext->isNew($this);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function isDirty(self $entity): bool
    {
        return $this->dbContext->isDirty($this);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function isDeleted(self $entity): bool
    {
        return $this->dbContext->isDeleted($this);
    }

    /**
     * Помечает только что созданную сущность как новую.
     */
    public function markNew(): self
    {
        $this->dbContext->registerNew($this);
        return $this;
    }

    /**
     * Помечает сущность как измененную.
     */
    public function markDirty(): self
    {
        $this->dbContext->registerDirty($this);
        return $this;
    }

    /**
     * Помечает сущность на удаление.
     */
    public function markDeleted(): self
    {
        $this->dbContext->registerDeleted($this);
        return $this;
    }

    # endregion

    # region Validation

    /**
     * @var Validator $validator
     */
    protected Validator $validator;

    /**
     * Возвращает список правил валидации
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Валидация полей сущности
     *
     * @param array|null $attributes массив полей
     *
     * @return boolean
     * @throws ValidationException
     */
    public function validate(array $attributes = null): bool
    {
        $this->errors = $this->validator->validate($this, $attributes);
        return empty($this->errors);
    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    public function getRules(string $fieldName): array
    {
        return $this->validator->getRules($this, $fieldName);
    }

    /**
     * добавляет ошибку к списку ошибок
     *
     * @param string $fieldName
     * @param string $message
     *
     * @return void
     */
    public function addError(string $fieldName, string $message): void
    {
        $this->validator->addError($fieldName, $message);
    }

    /**
     * Сообщение об ошибках
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }

    /**
     * Сообщение об ошибках
     *
     * @return string
     */
    public function getErrorsSummary(): string
    {
        return $this->validator->getErrorsSummary($this);
    }

    # endregion
}
