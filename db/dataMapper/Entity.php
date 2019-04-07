<?php
namespace tachyon\db\dataMapper;

use tachyon\validation\ValidationInterface,
    tachyon\db\dataMapper\DbContext,
    tachyon\validation\Validator;

abstract class Entity implements EntityInterface, UnitOfWorkInterface, ValidationInterface
{
    use \tachyon\traits\ClassName;
    
    /**
     * Имя таблицы БД
     * @var string
     */
    protected $tableName;
    /**
     * @var tachyon\db\dataMapper\DbContext
     */
    protected $dbContext;
    /**
     * @var tachyon\validation\Validator $validator
     */
    protected $validator;

    /**
     * @return void
     */
    public function __construct(DbContext $dbContext, Validator $validator)
    {
        $this->dbContext = $dbContext;
        $this->validator = $validator;
        if (is_null($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', $this->getClassName());
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr)) . 's';
        }
    }

    /**
     * @var array Подписи для поля сущностей
     */
    protected $attributeCaptions = array();
    /**
     * Первичный ключ
     * @var mixed
     */
    protected $pk = 'id';
    /**
     * Ошибки валидации
     * @var array $errors
     */
    protected $errors = array();

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->getOwner();
    }

    /**
     * Подпись для поля сущности
     * 
     * @param string $attribute имя сущности
     * @return string
     */
    public function getCaption(string $attribute): string
    {
        return $this->attributeCaptions[$attribute] ?? $attribute;
    }

    /**
     * Извлечение значения аттрибута $attribute
     * 
     * @param string $attribute 
     * @return mixed 
     */
    public function getAttribute($attribute)
    {
        $methodName = 'get' . ucfirst($attribute);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
    }

    /**
     * Присваивание значения $value аттрибуту $attribute
     * При этом сущность не помечается как измененная.
     * 
     * @param mixed $attribute 
     * @param mixed $value 
     */
    public function setAttribute($attribute, $value = null)
    {
        if (is_array($attribute)) {
            $value = current($attribute);
            $attribute = key($attribute);
        }
        $this->$attribute = $value;
    }

    protected function _setAttribute(string $attribute, string $value = null): Entity
    {
        if (!is_null($value)) {
            $this->$attribute = $value;
            $this->markDirty();
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
     * @return mixed
     */
    public function setPk($pk)
    {
        $this->{$this->pk} = $pk;
    }

    # Unit of work

    /**
     * @return DbContext
     */
    public function getDbContext()
    {
        return $this->dbContext;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isNew()
    {
        return $this->dbContext->isNew($this);
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isDirty(Entity $entity)
    {
        return $this->dbContext->isDirty($this);
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isDeleted(Entity $entity)
    {
        return $this->dbContext->isDeleted($this);
    }

    /**
     * Помечает только что созданую сущность как новую.
     */
    public function markNew()
    {
        $this->dbContext->registerNew($this);
        return $this;
    }

    /**
     * Помечает сущность как измененную.
     */
    public function markDirty()
    {
        $this->dbContext->registerDirty($this);
        return $this;
    }

    /**
     * Помечает сущность на удаление.
     */
    public function markDeleted()
    {
        $this->dbContext->registerDeleted($this);
        return $this;
    }

    /**
     * Сохранение одиночной сущности.
     * 
     * @return boolean
     */
    public function save()
    {
        return
                $this->validate()
            and $this->getDbContext()->commit()
        ;
    }

    /**
     * Удаление одиночной сущности.
     * 
     * @return boolean
     */
    public function delete()
    {
        return
                $this->markDeleted()
            and $this->getDbContext()->commit()
        ;
    }

    # Validation

    /**
     * Возвращает список правил валидации
     * 
     * @return array
     */
    public function rules(): array
    {
        return array();
    }

    /**
     * Валидация полей сущности
     * 
     * @param $attrs array массив полей
     * @return boolean
     */
    public function validate(array $attributes=null)
    {
        $this->errors = $this->validator->validate($this, $attributes);
        return empty($this->errors);
    }

    public function getRules($fieldName)
    {
        return $this->validator->getRules($this, $fieldName);
    }

    /**
     * добавляет ошибку к списку ошибок
     * 
     * @param string $attr
     * @param string $message
     * @return void
     */
    public function addError($attr, $message)
    {
        $this->validator->addError($attr, $message);
    }

    /**
     * Сообщение об ошибках
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->validator->getErrors();
    }

    /**
     * Сообщение об ошибках
     * 
     * @return array
     */
    public function getErrorsSummary()
    {
        return $this->validator->getErrorsSummary($this);
    }
}
