<?php
namespace tachyon\db\models;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 * 
 * Класс модели подзапроса
 */
abstract class QueryModel extends Model
{
    /**
     * алиас => код запроса
     */
    protected static $source;
    /**
     * Название таблицы в БД или алиаса
     */
    protected $tableName;

    /**
     * возвращает код запроса
     */

    /**
     * конструктор
     */
    public function __construct()
    {
        parent::__construct();

        $this->tableName = $this->getTableName();
    }
     
    /**
     * возвращает код запроса
     */
    public static function getSource()
    {
        $sourceVals = array_values(static::$source);
        return "({$sourceVals[0]})";
    }

    /**
     * возвращает алиас таблицы
     */
    public static function getTableName()
    {
        $sourceKeys = array_keys(static::$source);
        return $sourceKeys[0];
    }
    
    public function getQuery()
    {
        return $this->getSource() . " AS " . $this->getTableName();
    }
        
    // TODO: преобразовать findAll() и убрать эти методы
    
    /**
     * поле первичного ключа
     * 
     * @return array
     */
    /*final public static function getPrimKey()
    {
        return null;
    }*/
        
    /**
     * возвращает первичный ключ
     * 
     * @return array
     */
    /*final public static function getPrimKeyArr()
    {
        return array();
    }*/

    /**
     * алиас первичного ключа
     * 
     * @return array
     */
    /*final public static function getPrimKeyAliasArr()
    {
        return array();
    }*/

    /**
     * возвращает типы аттрибутов модели
     * 
     * @return array
     */
    /*final public static function getAttributeTypes()
    {
        return array();
    }*/
}