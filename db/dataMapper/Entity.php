<?php
namespace tachyon\db\dataMapper;

abstract class Entity
{
    /**
     * @var array Подписи для поля сущностей
     */
    protected $attributeCaptions = array();

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
}
