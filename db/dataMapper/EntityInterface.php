<?php
namespace tachyon\db\dataMapper;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
interface EntityInterface
{
    /**
     * @return DbContext
     */
    public function getDbContext();

    /**
     * @return Repository
     */
    public function getRepository();

    public function fromState(array $state): Entity;

    public function setAttributes(array $state);

    public function getAttributes(): array;

    /**
     * Подпись для поля сущности
     * 
     * @param string $attribute имя сущности
     * @return string
     */
    public function getCaption(string $attribute): string;

    /**
     * Извлечение значения аттрибута $attribute
     * 
     * @param string $attribute 
     * @return mixed 
     */
    public function getAttribute($attribute);

    /**
     * Присваивание значения $value аттрибуту $attribute
     * 
     * @param string $attribute 
     * @param mixed $value 
     */
    public function setAttribute(string $attribute, $value);

    /**
     * Имя поля первичного ключа
     * 
     * @return string
     */
    public function getPkName(): string;

    /**
     * Значение первичного ключа
     * 
     * @return mixed
     */
    public function getPk();

    # UNIT OF WORK

    /**
     * Помечает только что созданую сущность как новую.
     */
    public function markNew();

    /**
     * Помечает сущность как измененную.
     */
    public function markDirty();

    /**
     * Помечает сущность на удаление.
     */
    public function markDeleted();
}
