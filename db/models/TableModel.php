<?php
namespace tachyon\db\models;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 * 
 * Класс модели таблицы. Реализует ActiveRecord
 */
abstract class TableModel extends Model
{
    use \tachyon\dic\Db;
    use \tachyon\dic\Join;
    use \tachyon\dic\Alias;

    /**
     * Название таблицы в БД или алиаса
     */
    public static $tableName;
    /**
     * первичный ключ
     */
    public static $primKey;

    /**
     * маркер: новая это несохраненная модель или извлеченная из БД
     */
    protected $isNew = true;
    /**
     * маркер: изменившаяся несохраненная модель
     * пока не используется
     */
    protected $isDirty = false;
    /**
     * Поля выборки
     */
    protected $selectFields = array();

    /**
     * SQL-типы полей таблицы
     */
    protected static $fieldTypes = array();
    /**
     * Алиас текущей (главной) таблицы запроса
     */
    protected $tableAlias;
    /**
     * Поля, по которым таблица сортируется с помощью ORDER BY CAST
     * @var $scalarFields array
     */
    protected $scalarFields = array();
    /**
     * Поля, по которым таблица сортируется по умолчанию
     * @var $defSortBy array
     */
    protected $defSortBy = array();

    /**
     * конструктор
     */
    public function __construct()
    {
        // проверяем наличие таблицы в БД
        if (!$this->getDb()->isTableExists(static::$tableName))
            throw new \Exception('Table "' . static::$tableName . '" does not exist');
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return !is_null($this->tableAlias) ? $this->tableAlias : static::$tableName;
    }

    ##################################
    #                                #
    #  МЕТОДЫ ВЫБОРКИ СТРОК ТАБЛИЦЫ  #
    #                                #
    ##################################

    /**
     * Все записи в виде массивов
     * @return array
     */
    public function getAll()
    {
        $this->setDefaultSortBy();
        // устанавливаем массив полей для выборки
        $this->setSelect();
        // добавляем внешний(е) ключ(и) если его(их) нет в массиве полей для выборки
        $this->addPkToSelect();
        // поля данной модели (без присоединенных ч/з JOIN)
        $modelFields = $this->getSelect();
        $tableName = static::$tableName;
        // алиасим поля и присобачиваем к массиву полей для выборки
        $this->selectFields = array_merge($this->selectFields, $this->getAlias()->aliasFields($modelFields, $tableName));
        // устанавливаем поля для выборки
        $this->select($this->selectFields);

        $this->getAlias()->prependTableNameOnWhere($tableName, $this->getWhere(), $this);
        // алиасим имя таблицы
        if (!is_null($this->tableAlias))
            $tableName .= " AS {$this->tableAlias}";

        // выбираем записи
        $items = $this->getDb()->select($tableName);
        $this->clearSelect();
        $this->clearAlias();
        return $items;
    }

    /**
     * getAllByAttrs
     * 
     * @param $attrs array массив поле=>значение
     * @return array
     */
    public function getAllByAttrs(array $attrs)
    {
        return $this
            ->where($attrs)
            ->getAll();
    }

    /**
     * getOne
     * 
     * @return array
     */
    public function getOne()
    {
        $this->getDb()->setLimit(1);
        if ($items = $this->getAll())
            return $items[0];
    }

    /**
     * @param $attrs array массив поле=>значение
     * @return array
     */
    public function getOneByAttrs(array $attrs)
    {
        return $this
            ->where($attrs)
            ->getOne();
    }

    /**
     * @param $conditions array
     * @return array
     */
    public function getAllByConditions($conditions=array())
    {
        $items = $this
            ->addWhere($conditions)
            ->getAll();

        return $items;
    }

    /**
     * save
     * Сохраняет модель в БД. При вставке строки возвращает $pk модели
     * 
     * @param $validate boolean производить ли валидацию
     * @return integer
     */
    public function save($validate=true)
    {
        if ($validate && !$this->validate())
            return false;

        $result = ($this->isNew) ? $this->insert() : $this->update();
        if ($result!==false)
            $this->afterSave();

        return $result;
    }

    /**
     * saveAttrs
     * Сохраняет набор полей модели в БД
     * 
     * @param $attrs array массив поле=>значение
     * @param $validate boolean производить ли валидацию
     * @return integer
     */
    public function saveAttrs(array $attrs, $validate=false)
    {
        $this->setAttributes($attrs);
        if ($validate && !$this->validate(array_keys($attrs)))
            return false;

        return $this->update();
    }

    /**
     * afterSave
     * Хук на событие сохранения модели
     * @return boolean
     */
    protected function afterSave()
    {
        return true;
    }

    /**
     * вставляет строку в БД
     * возвращает $pk модели
     * 
     * @return integer
     */
    public function insert()
    {
        if (!$lastInsertId = $this->getDb()->insert(static::$tableName, $this->fieldAttributes()))
            return false;
        
        $pk = static::$primKey;
        $this->$pk = $lastInsertId;
        return $lastInsertId;
    }

    /**
     * сохраняет модель в БД
     * 
     * @return integer
     */
    public function update()
    {
        $condition = array();
        $pk = static::$primKey;
        if (is_array($pk))
            foreach ($pk as $key)
                $condition[$key] = $this->$key;
        else
            $condition[$pk] = $this->$pk;

        return $this->getDb()->update(static::$tableName, $this->fieldAttributes(), $condition);
    }
    
    /**
     * удаляет модель из БД
     */
    public function delete()
    {
        $pk = static::$primKey;
        if ($this->$pk)
            if ($this->getDb()->delete(static::$tableName, array($pk => $this->$pk))) {
                unset($this);
                return true;
            }

        return false;
    }

    /**
     * deleteAllByAttrs
     * Удаляет все модели, соотв. условиям
     * 
     * @param $attrs array массив поле=>значение
     * @return boolean
     */
    public function deleteAllByAttrs(array $attrs)
    {
        return $this->getDb()->delete(static::$tableName, $attrs);
    }

    /**
     * очищаем таблицу
     */
    public static function clear()
    {
        \tachyon\dic\Container::getInstanceOf('db')->truncate(static::$tableName);
    }

    /**
     * преобразование значений полей (в т.ч. timestamp)
     * // TODO: убрать
     */
    private function convVals($selectedFields, $relationFields)
    {
        if (count($selectedFields)==0)
            return $selectedFields;

        return array_combine(
            array_keys($selectedFields),
            array_map(
                function($key, $val) use ($relationFields) {
                    if (!isset($relationFields[$key]))
                        return $val;
                    $fieldType = $relationFields[$key];
                    switch ($fieldType) {
                        case 'timestamp':
                            return \tachyon\helpers\DateTimeHelper::timestampToDateTime($val);
                        default:
                            return $val;
                    }
                },
                array_keys($selectedFields),
                array_values($selectedFields)
            )
        );
    }

    /**
     * scalarAttributes
     * Только поля таблицы
     * @return array
     */
    public function fieldAttributes()
    {
        return array_intersect_key($this->attributes, array_flip(static::$fields));
    }

    /**
     * Присваивание значений аттрибутам модели
     * @param $arr array 
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value)
            if (in_array($name, static::$fields))
                $this->attributes[$name] = $value;
    }

    ############################################
    #                                          #
    # ЗДЕСЬ ЗАДАЮТСЯ РАЗЛИЧНЫЕ УСЛОВИЯ ВЫБОРКИ #
    #                                          #
    ############################################

    # WHERE
    
    /**
     * setSearchConditions
     * Устанавливает специфические для модели условия для поиска
     * 
     * @param $attrs array 
     * @return TableModel
     */
    public function setSearchConditions($where=array())
    {
        $this->where($where);
        return $this;
    }

    public function where($where)
    {
        $this->getDb()->setWhere($this->_prepareWhere($where));
        return $this;
    }

    public function addWhere($where)
    {
        $this->getDb()->addWhere($this->_prepareWhere($where));
        return $this;
    }

    public function getWhere()
    {
        return $this->getDb()->getWhere();
    }

    /**
     * like
     * Устанавливает условие LIKE
     * 
     * @param $where array 
     * @param $field string
     * 
     * @return TableModel
     */
    public function like($where, $field)
    {
        $where = $this->_prepareWhere($where);
        if (isset($where[$field]))
            $this->getDb()->addWhere(array("$field LIKE" => $where[$field]));

        return $this;
    }

    /**
     * greatThen
     * Устанавливает условие больше чем
     * 
     * @param $where array массив условий
     * @param $field string поле на котором устанавливается условие
     * @param $arrKey string ключ массива условий
     * @param $precise "меньше" или "меньше или равно"
     * 
     * @return TableModel
     */
    public function gt(&$where, $field, $arrKey, $precise=false)
    {
        if (isset($where[$arrKey])) {
            $this->getDb()->addWhere(array_filter(array("$field>" . ($precise ? '' : '=') => $where[$arrKey])));
            unset($where[$arrKey]);
        }
        return $this;
    }

    /**
     * lessThen
     * Устанавливает условие меньше чем
     * 
     * @param $where array массив условий
     * @param $field string поле на котором устанавливается условие
     * @param $arrKey string ключ массива условий
     * @param $precise "меньше" или "меньше или равно"
     * 
     * @return TableModel
     */
    public function lt(&$where, $field, $arrKey, $precise=false)
    {
        if (isset($where[$arrKey])) {
            $this->getDb()->addWhere(array_filter(array("$field<" . ($precise ? '' : '=') => $where[$arrKey])));
            unset($where[$arrKey]);
        }
        return $this;
    }

    /**
     * @param $where array 
     * @return array
     */
    private function _prepareWhere($where)
    {
        foreach ($where as $field => &$value) {
            if (!isset(static::$fieldTypes[$field]))
                continue;

            $type = static::$fieldTypes[$field];
            if (strpos($type, 'text')!==false)
                $value = "'$value'";
        }
        return $where;
    }

    # ORDER BY

    public function setSortConditions($attrs)
    {
        if (isset($attrs['order']))
            $this->sortBy($attrs['field'], $attrs['order']);

        return $this;
    }

    /**
     * добавление сортировки выбранных записей по полю $sortBy
     */
    public function sortBy($colName, $order='ASC')
    {
        $colName = $this->_orderByCast($colName);
        $this->getDb()->orderBy($colName, $order);
        return $this; 
    }
    
    /**
     * сортировка выбранных записей только по полю $sortBy
     */
    public function setSortBy(array $sortBy)
    {
        $this->getDb()->setOrderBy($sortBy);
    }

    public function getSortBy()
    {
        return $this->getDb()->getOrderBy();
    }

    /**
     * устанавливаем массив полей для сортировки по умолчанию
     */
    protected function setDefaultSortBy()
    {
        if (empty($this->getSortBy()))
            foreach ($this->defSortBy as $key => $val) {
                if (is_numeric($key)) {
                    $order = 'ASC';
                    $colName = $val;
                } else {
                    $order = $val;
                    $colName = $key;
                }
                $colName = $this->getAlias()->aliasField($colName, static::$tableName);
                $colName = $this->_orderByCast($colName);
                $this->getDb()->orderBy($colName, $order);
            }
    }

    /**
     * _setSortByCast
     * 
     * @param $colName string
     * @return string
     */
    private function _orderByCast($colName)
    {
        $searchColName = str_replace("{$this->getTableAlias()}.", '', $colName);
        if (in_array($searchColName, $this->scalarFields))
            return "CAST($colName as unsigned)";

        return $colName;
    }
    
    # LIMIT
    
    /**
     * ограничение числа выбранных записей
     */
    public function limit($limit, $offset=null)
    {
        $this->getDb()->setLimit($limit, $offset);
        return $this; 
    }
    
    public function getLimit()
    {
        return $this->getDb()->getLimit();
    }

    # GROUP BY
    
    /**
     * группировка выбранных записей по полю $fieldName
     */
    public function groupBy($fieldName)
    {
        $this->getDb()->setGroupBy($fieldName);
        return $this;
    }

    public function getGroupBy()
    {
        return $this->getDb()->getGroupBy();
    }

    /**
     * задает поля для селекта
     */
    public function select()
    {
        $fieldNames = func_get_args();
        if (is_array($fieldNames[0]))
            $fieldNames = $fieldNames[0];

        $all = false;
        foreach ($fieldNames as $key => $fieldName) {
            if (!is_numeric($key)) {
                $fieldNames[] = "$key AS $fieldName";
                unset($fieldNames[$key]);
            }
            if ($fieldName==='*') {
                unset($fieldNames[$key]);
                $all = true;
            }
        }
        if ($all) {
            foreach (static::$fields as $field) {
                $fieldNames[] = $field;
            }
        }
        $this->getDb()->setFields($fieldNames);
        return $this;
    }

    /**
     * устанавливаем массив полей для выборки
     */
    public function setSelect()
    {
        // если он пуст 
        if (empty($this->getSelect()))
            $this->select(static::getTableFields());
    }

    /**
     * поля для селекта/апдейта
     */
    public function getSelect()
    {
        return $this->getDb()->getFields();
    }

    /**
     * очищает поля выборки
     */
    public function clearSelect()
    {
        $this->selectFields = array();
    }    

    # JOIN (шорткаты)

    public function join($join, $on=array(), $tblName=null)
    {
        return $this->leftJoin($join, $on, $tblName, $this); 
    }

    public function innerJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->innerJoin($join, $on, $tblName, $this);
        return $this; 
    }

    public function leftJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->leftJoin($join, $on, $tblName, $this);
        return $this; 
    }

    public function rightJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->rightJoin($join, $on, $tblName, $this);
        return $this; 
    }

    public function outerJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->outerJoin($join, $on, $tblName, $this);
        return $this; 
    }

    /**
     * asa
     * Устанавливает алиас текущей (главной) таблицы запроса
     */
    public function asa($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * очищает поля алиас таблицы
     */
    public function clearAlias()
    {
        $this->tableAlias = null;
    }    

    /**
     * добавляем внешний(е) ключ(и) если его(их) 
     * нет в массиве полей для выборки
     */
    public function addPkToSelect()
    {
        // берем массив полей для выборки, заданный $this->select()
        $modelFields = $this->getSelect();
        $pk = static::$primKey;
        if (is_array($pk)) {
            foreach ($pk as $key)
                if (!in_array($key, $modelFields)) 
                    $modelFields[] = $key;
        } elseif (!in_array($pk, $modelFields)) 
            $modelFields[] = $pk;
            
        $this->select($modelFields);
    }

    # геттеры

    /**
     * fieldsList
     * список полей таблицы ч/з запятую
     * @return string
     */
    protected function getFieldsList()
    {
        $fields = $this->getAlias()->aliasFields(static::$fields, static::$tableName);
        return implode(',', $fields);
    }

    /**
     * Возвращает название таблицы в БД
     */
    public static function getTableName()
    {
        return static::$tableName;
    }

    /**
     * возвращает список полей таблицы
     */
    public static function getTableFields()
    {
        return static::$fields;
    }

    /**
     * возвращает первичный ключ
     */
    public static function getPrimKey()
    {
        return static::$primKey;
    }

    /**
     * возвращает первичный ключ, приведенный в одну форму
     */
    public static function getPrimKeyArr()
    {
        if (!$primKey = static::$primKey)
            throw new \Exception('Не объявлен первичный ключ связанной таблицы');
        if (is_array($primKey))
            return $primKey;
            
        return array($primKey);
    }

    /**
     * возвращает значение первичного ключа
     */
    public function getPrimKeyVal()
    {
        return $this->{static::$primKey};
    }

    # Геттеры и сеттеры

    public function getIsNew()
    {
        return $this->isNew;
    }

    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    public function getSelectFields()
    {
        return $this->selectFields;
    }

    public function setSelectFields($selectFields)
    {
        $this->selectFields = $selectFields;
    }
}
