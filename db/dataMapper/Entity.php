<?php
namespace tachyon\db\dataMapper;

abstract class Entity
{
    use \tachyon\dic\Validator;

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
     * Сущность нуждается в сохранении в хранилище
     * Это свойство нужно для реализации Unit of Work
     * @var boolean
     */
    protected $dirty = false;

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

    /**
     * Первичный ключ
     * 
     * @return mixed
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * @return void
     */
    public function markDirty(): Entity
    {
        $this->dirty = true;
        return $this;
    }
}
