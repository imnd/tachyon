<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait GetList
{
    /**
     * Список для select`а из массива строк таблицы $items
     * @return array
     */
    public static function getSelectList()
    {
        $modelName = \tachyon\helpers\StringHelper::getShortClassName(get_called_class());
        $model = \tachyon\dic\Container::getInstanceOf($modelName);
        return $model->getListBehaviour()->getSelectList($model->getAll());
    }
}
