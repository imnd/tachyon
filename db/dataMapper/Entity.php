<?php
namespace tachyon\db\dataMapper;

use tachyon\validation\ValidationInterface,
    tachyon\db\dataMapper\DbContext,
    tachyon\validation\Validator;

abstract class Entity implements EntityInterface, ValidationInterface
{
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
     * @return DbContext
     */
    public function getDbContext()
    {
        return $this->dbContext;
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
     * 
     * @param string $attribute 
     * @param mixed $value 
     */
    public function setAttribute(string $attribute, $value)
    {
        $methodName = 'set' . ucfirst($attribute);
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        }
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

    # UNIT OF WORK

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

    # VALIDATION

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
