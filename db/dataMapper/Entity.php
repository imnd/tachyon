<?php
namespace tachyon\db\dataMapper;

abstract class Entity extends \tachyon\Component
{
    use \tachyon\dic\Validator;
    use \tachyon\dic\DbContext;

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
        return $this->getDomain();
    }

    abstract public function fromState(array $state): Entity;

    abstract public function setAttributes(array $state);

    abstract public function getAttributes(): array;

    /**
     * Подпись для поля сущности
     * 
     * @param string $attribute имя сущности
     * @return string
     */
    public function getAttributeCaption(string $attribute): string
    {
        return $this->attributeCaptions[$attribute] ?? $attribute;
    }

    public function getAttribute($attribute)
    {
        $methodName = 'get' . ucfirst($attribute);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
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
}
