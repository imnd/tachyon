<?php
namespace tachyon\db\activeRecord;

use tachyon\exceptions\ModelException;

/**
 * Класс модели Active Record
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class ActiveRecord extends \tachyon\Model
{
    use \tachyon\dic\Alias;
    use \tachyon\dic\DbCache;
    use \tachyon\dic\DbFactory;
    use \tachyon\dic\Join;
    use \tachyon\dic\Message;
    use \tachyon\dic\Terms;

    /**
     * Имя таблицы в БД или алиаса
     */
    protected static $tableName;
    /**
     * Имя поля первичного ключа
     */
    protected $pkName;
    /**
     * Поля таблицы
     * @var array
     */
    protected $fields = array();
    /**
     * Поля выборки
     */
    protected $selectFields = array();
    /**
     * SQL-типы полей таблицы
     */
    protected $fieldTypes = array();
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

    protected $entityNames = array();
    /**
     * СВЯЗАННЫЕ Ч/З ВНЕШНИЕ КЛЮЧИ МОДЕЛИ
     * 
     * 'has_one': связь "один к одному" вида
     * array('название объекта' => array(
     *      'внешняя модель',
     *      'has_one',
     *      'внешний ключ данной таблицы, указывающий на первичный ключ внешней таблицы',
     *      array(список интересующих полей)(optional, если пропущен, выбираются все поля, объявленные в св-ве $attributes внешней модели)
     * ));
     * 
     * 'has_many': связь "один ко многим" вида
     * array('название объекта' => array(
     *      'внешняя модель',
     *      'has_many',
     *      'внешний ключ внешней таблицы, указывающий на первичный ключ данной таблицы',
     *      array(список интересующих полей) (optional, если пропущен, выбираются все поля, объявленные в св-ве $attributes внешней модели) 
     * ));
     * 
     * 'many_to_many': связь "многие ко многим" вида
     * array('название объекта' => array(
     *      'внешняя модель',
     *      'many_to_many',
     *      array(
     *          'модель связи', 'внешний ключ расшивочной таблицы, указывающий на первичный ключ таблицы модели', 'внешний ключ расшивочной таблицы, указывающий на первичный ключ внешней таблицы', array(список интересующих полей расшивочной таблицы)(optional) 
     *      )
     *      array(список интересующих полей)(optional, если пропущен, выбираются все поля, объявленные в св-ве $attributes внешней модели) 
     * ));
     * 
     * 'join': подзапрос. связь "один ко многим" вида
     * array('название объекта' => array(
     *      'внешняя модель',
     *      'join',
     *      'внешний ключ внешней таблицы, указывающий на первичный ключ данной таблицы',
     *      array(список интересующих полей) (optional, если пропущен, выбираются все поля, объявленные в св-ве $attributes внешней модели) 
     * ));
     */
    protected $relations = array();
    /**
     * связанные модели, по которым происходит выборка
     */
    protected $with = array();

    /**
     * маркер: новая это несохраненная модель или извлеченная из БД
     */
    protected $isNew = true;
    /**
     * маркер: изменившаяся несохраненная модель
     * пока не используется
     */
    protected $isDirty = false;

    protected $relationClasses = array();

    /**
     * Инициализация
     * @return void
     * @throws ModelException
     */
    public function __construct()
    {
        if (is_null(static::$tableName)) {
            throw new ModelException($this->msg->i18n('Property "tableName" is not set'));
        }
        // добавляем внешние ключи по объявленным связям
        foreach ($this->relations as $with => &$relationParams) {
            $relationModel = $this->get($relationParams[0]);
            // приделываем поля связанной таблицы (если не заданы в связи)
            if (!isset($relationParams[3]))
                $relationParams[3] = $relationModel->getTableFields();

            // приделываем первичные ключи связанной таблицы
            $relationParams[3] = array_merge($relationParams[3], $relationModel->getPkArr());
            if (get_parent_class($relationModel)==='\tachyon\db\activeRecord\ActiveRecord') {
                // приделываем алиасы первичных ключей связанной таблицы
                $relationParams[3] = array_merge($relationParams[3], $relationModel->alias->getPrimKeyAliasArr($with));
            }
        }
        return;
    }

    /**
     * Лениво извлекаем связанные записи
     */
    public function __get($var)
    {
        // если это не подключенное ч/з with св-во ("жадная" загрузка)
        // ищем среди присоединенных ч/з внешние ключи объектов
        // TODO: перенести в Relations
        if (isset($this->relations[$var])) {
            $relationArr = $this->relations[$var];
            $relModel = $this->get($relationArr[0]);
            $type = $relationArr[1];
            // первичный ключ внешней таблицы
            $relPk = $relModel->getPkName();
            switch ($type) {
                case 'has_one':
                    $fk = $relationArr[2];
                    $where = is_array($fk) ? array($fk[1] => $this->{$fk[0]}) : array($relPk=> $this->$fk);
                    return $relModel
                        ->where($where)
                        ->findOne();
                break;

                case 'has_many':
                    // первичный ключ этой таблицы
                    $thisPk = $this->pkName;
                    // внешний ключ связанной таблицы, указывающий на первичный ключ этой таблицы
                    $fk = $relationArr[2];
                    return $relModel
                        ->where(array($fk => $this->$thisPk))
                        ->findAll();
                break;

                case 'many_to_many':
                    $pk = $this->pkName;
                    $lnkModel = $this->get($relationArr[2][0]);
                    $linkTableName = $lnkModel->getTableName();
                    $relFk1 = $relationArr[2][1];
                    $relFk2 = $relationArr[2][2];
                    $relTableName = $relModel->getTableName();
                    $thisTableName = $this->getTableName();
                    if (isset($relationArr[2][3])) {
                        foreach ($relationArr[2][3] as $fieldName) {
                            $relModel->select("$linkTableName.$fieldName");
                        }
                    }
                    return $relModel
                        ->join($linkTableName, "$linkTableName.$relFk2=$relTableName.$relPk")
                        ->join($thisTableName, "$linkTableName.$relFk1=$thisTableName.$pk")
                        ->where(array("$thisTableName.$pk"=> $this->$pk))
                        ->findAll();
                break;
                
                case 'belongs_to':
                    // внешний ключ данной таблицы, указывающий на первичный ключ внешней таблицы
                    $relFk = $relationArr[2];
                    return $relModel
                        ->where(array($relPk => $this->$relFk))
                        ->findOne();
                break;

                case 'join':
                    $linkKey = $relationArr[2];
                    $relTableName = $relModel->getTableName();
                    $pk = $this->pkName;
                    $thisTableName = $this->getTableName();
                    $this->getDb()->setJoin("{$relModel->getSource()} AS $relTableName", "$relTableName.$linkKey=$thisTableName.$pk");
                    $this->getDb()->setFields($relationArr[3]);
                    return $this->getDb()->selectOne($thisTableName, array("$thisTableName.$pk" => $this->$pk));
                break;

                default: break;
            }
        }
        return parent::__get($var);
    }

    ##################################
    #                                #
    #  МЕТОДЫ ВЫБОРКИ СТРОК ТАБЛИЦЫ  #
    #                                #
    ##################################

    /**
     * Все записи в виде массивов
     * @param array $conditions условия поиска массив поле => значение
     * @return array
     */
    public function findAllScalar(array $conditions = array()): array
    {
        if (!empty($conditions)) {
            $this->addWhere($conditions);
        }
        $this->setDefaultSortBy();
        // устанавливаем массив полей для выборки
        $this->setSelect();
        // добавляем внешний(е) ключ(и) если его(их) нет в массиве полей для выборки
        $this->addPkToSelect();
        // поля данной модели (без присоединенных ч/з JOIN)
        $modelFields = $this->getSelect();
        $tableName = static::$tableName;
        // алиасим поля и присобачиваем к массиву полей для выборки
        $this->selectFields = array_merge($this->selectFields, $this->alias->aliasFields($modelFields, $tableName));
        // устанавливаем поля для выборки
        $this->select($this->selectFields);

        $this->alias->prependTableNameOnWhere($tableName, $this->getWhere());
        // алиасим имя таблицы
        if (!is_null($this->tableAlias)) {
            $tableName .= " AS {$this->tableAlias}";
        }
        // выбираем записи
        $items = $this->getDb()->select($tableName);
        $this->clearSelect();
        $this->clearAlias();
        return $items;
    }

    /**
     * @param $attrs array массив поле=>значение
     * @return array
     */
    public function findOneScalar(array $conditions = array())
    {
        $this->getDb()->setLimit(1);
        if ($items = $this->findAllScalar($conditions)) {
            return $items[0];
        }
    }

    /**
     * Возвращает набор строк по условию
     * 
     * @return array
     */
    public function findAll($conditions = array())
    {
        if (!empty($conditions)) {
            $this->addWhere($conditions);
        }
        // кеширование
        $cacheKey = json_encode($this->getTableName())
                  . json_encode($this->getSelect())
                  . json_encode($this->getWhere())
                  . json_encode($this->getSortBy())
                  . $this->getLimit()
                  . $this->getGroupBy()
                  . json_encode($this->with);

        if ($items = $this->cache->start($cacheKey))
            return $items;

        $this->setDefaultSortBy();

        // для алиасинга имен таблиц в условиях и группировках
        $tableAliases = array();
        // приделываем запросы выборки внешних объектов
        foreach ($this->with as $with) {
            $relation = $this->relationClasses[$with];
            $tableAliases[$relation->getTableName()] = $relation->getTableAlias();
            $this->selectFields = array_merge($this->selectFields, $relation->getFields());
            $relation->joinWith($this);
        }
        // поля данной модели (без присоединенных ч/з JOIN)
        // алиасим имена таблиц в массиве полей для выборки, заданный $this->select() 
        $this->alias->aliasSelectTableNames($tableAliases, $this);
        // устанавливаем массив полей для выборки
        $this->setSelect();
        // добавляем внешний(е) ключ(и) если его(их) нет в массиве полей для выборки
        $this->addPkToSelect();
        // алиасим поля и присобачиваем к массиву полей для выборки
        $tableName = static::$tableName;
        $modelFields = $this->getSelect();
        $this->selectFields = array_merge($this->selectFields, $this->alias->aliasFields($modelFields, $tableName));
        // устанавливаем поля для выборки
        $this->select($this->selectFields);
        // алиасим имена таблиц в groupBy
        $this->alias->aliasGroupByTableName($tableAliases, $this);
        // алиасим имена таблиц в sortBy
        $this->alias->aliasSortByTableName($tableAliases, $this);
        // алиасим имена таблиц в условиях
        $this->alias->aliasWhereTableNames($tableAliases, $this);

        // ВЫБИРАЕМ ЗАПИСИ
        if (!$items = $this->getDb()->select($tableName)) {
            return array();
        }
        $this->clearSelect();

        $retItems = array();

        $modelName = $this->getClassName();
        $modelFieldsKeys = array_flip($this->alias->getAliases($modelFields));
        foreach ($items as $item) {
            /*
            // преобразование полей (в т.ч. timestamp) // TODO: убрать отсюда!!!
            $item = $this->_convVals($item, $this->selectFields);
            */
            $itemPk = $item[$this->pkName];
            // чтобы не перезаписывать данные основной записи в случае JOIN
            if (!array_key_exists($itemPk, $retItems)) {
                // берём только поля данной модели (без присоединенных ч/з JOIN)
                $model = $this->get($modelName);
                $model->with($this->with);
                $model->setAttributes(array_intersect_key($item, $modelFieldsKeys));
                $model->setAttribute($this->pkName, $itemPk);
                $retItems[$itemPk] = $model;
            }
            // приделываем внешние объекты
            // заполняем внешние поля, присоединенные ч/з JOIN
            foreach ($this->with as $with) {
                /** @var \tachyon\db\activeRecord\Relation $relation */
                $relation = $this->relationClasses[$with];
                // выбираем значения внешних полей
                $relation->setValues($item);
                if (count($relation->getValues())===0)
                    continue;

                // убираем суффиксы у ключей
                $relation->trimSuffixes($with);
                /*
                // преобразование полей (в т.ч. timestamp) // TODO: убрать отсюда
                $attributeTypes = $relation->modelName::getAttributeTypes();
                $relation->values = $this->_convVals($relation->values, $attributeTypes);
                */
                /*if (in_array(null, $relation->values)) {
                    $retItems[$itemPk]->$with = array();
                } else {*/
                    $relation->setModelAttrs();
                    $retItems[$itemPk] = $relation->attachWithObject($retItems[$itemPk], $with);
                /*}*/
            }
        }
        $this->clearWith();
        $items = array_values($retItems);

        $this->cache->end($items);

        return $items;
    }

    /**
     * преобразование значений полей (в т.ч. timestamp)
     * // TODO: убрать
     */
    private function _convVals($selectedFields, $relationFields)
    {
        if (count($selectedFields)==0)
            return $selectedFields;

        return array_combine(
            array_keys($selectedFields),
            array_map(
                function($key, $val) use ($relationFields) {
                    if (!isset($relationFields[$key])) {
                        return $val;
                    }
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
     * search
     * Поиск записей в соотв с заданными св-вами модели
     */
    public function search($fields=null)
    {
        $where = array();
        $tableName = $this->getTableName();
        foreach ($this->attributes as $key => $value)
            if (!is_null($value))
                $where["$tableName.$key"] = $value;

        $this->where($where);
        if (!is_null($fields))
            $this->select($fields);
        return $this->findAll();
    }

    /**
     * shortcut
     * @return \tachyon\db\activeRecord\ActiveRecord
     */
    public function findByPk($pk)
    {
        $pkName = $this->pkName;
        if (is_array($pkName)) {
            $conditions = array_combine($pkName, $pk);
        } elseif (is_string($pkName)) {
            $primKeyArr = $this->alias->aliasFields(array($pkName), static::$tableName);
            $pkName = $primKeyArr[0];
            $conditions = array($pkName => $pk);
        }
        return $this->findOne($conditions);
    }

    /**
     * @return \tachyon\db\activeRecord\ActiveRecord
     */
    public function findOne($conditions = array())
    {
        if ($items = $this
            ->limit(1)
            ->findAll($conditions)
        ) {
            $item = $items[0];
            $item->isNew = false;
            return $item;
        }
    }

    ###################################
    #                                 #
    #  МЕТОДЫ МАНИПУЛЯЦИИ С ТАБЛИЦЕЙ  #
    #                                 #
    ###################################

    /**
     * Сохраняет модель в БД. При вставке строки возвращает $pk модели
     * 
     * @param $validate boolean производить ли валидацию
     * @return integer
     */
    public function save($validate=true)
    {
        if ($validate && !$this->validate())
            return false;

        $result = $this->isNew ? $this->insert() : $this->update();
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
    protected function afterSave(): bool
    {
        return true;
    }

    /**
     * вставляет модель в БД возвращает $pk модели
     * 
     * @return mixed
     */
    public function insert()
    {
        if (!$lastInsertId = $this->getDb()->insert(static::$tableName, $this->fieldAttributes())) {
            return false;
        }
        return $this->{$this->pkName} = $lastInsertId;
    }

    /**
     * Сохраняет модель в БД
     * 
     * @return integer
     */
    public function update()
    {
        $condition = array();
        $pk = $this->pkName;
        if (is_array($pk)) {
            foreach ($pk as $key)
                $condition[$key] = $this->$key;
        } else {
            $condition[$pk] = $this->$pk;
        }
        return $this->getDb()->update(static::$tableName, $this->fieldAttributes(), $condition);
    }
    
    /**
     * удаляет модель из БД
     */
    public function delete(): bool
    {
        if (null!==$pk = $this->{$this->pkName})
            if ($this->getDb()->delete(static::$tableName, array($this->pkName => $pk))) {
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
     * удаляем модели, связанные с текущей ч/з has_many
     */
    public function deleteRelatedModels($relName)
    {
        foreach ($this->$relName as $relModel) {
            $relModel->delete();
            unset($relModel);
        }
    }

    /**
     * очищаем таблицу
     */
    public static function clear()
    {
        $this->getDb()->truncate(static::$tableName);
    }

    /**
     * Присваивание значений аттрибутам модели
     * @param $arr array 
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if (in_array($name, $this->fields)) {
                $this->attributes[$name] = $value;
            }
        }
        return $this;
    }

    ############################################
    #                                          #
    # ЗДЕСЬ ЗАДАЮТСЯ РАЗЛИЧНЫЕ УСЛОВИЯ ВЫБОРКИ #
    #                                          #
    ############################################

    # WHERE

    public function getWhere()
    {
        return $this->getDb()->getWhere();
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

    /**
     * @param $where array 
     * @return array
     */
    private function _prepareWhere(array $where)
    {
        foreach ($where as $field => &$value) {
            if (!isset($this->fieldTypes[$field])) {
                continue;
            }
            $type = $this->fieldTypes[$field];
            if (strpos($type, 'text')!==false) {
                $value = "'$value'";
            }
        }
        return $where;
    }

    /**
     * Устанавливает условия поиска для модели
     * 
     * @param $where array 
     * @return ActiveRecord
     */
    public function setSearchConditions(array $conditions=array())
    {
        $this->addWhere($conditions);
        return $this;
    }

    public function gt(array &$where, $field, $arrKey, $precise=false)
    {
        $this->getDb()->addWhere($this->terms->gt($where, $field, $arrKey, $precise));
        return $this;
    }

    public function lt(array &$where, $field, $arrKey, $precise=false)
    {
        $this->getDb()->addWhere($this->terms->lt($where, $field, $arrKey, $precise));
        return $this;
    }

    /**
     * Устанавливает условие LIKE
     * 
     * @param $where array 
     * @param $field string
     */
    public function like(array $where, $field)
    {
        $this->getDb()->addWhere($this->terms->like($where, $field));
        return $this;
    }

    # ORDER BY

    public function setSortConditions($attrs)
    {
        if (isset($attrs['order'])) {
            $this->sortBy($attrs['field'], $attrs['order']);
        }
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
        return $this;
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
                $colName = $this->alias->aliasField($colName, static::$tableName);
                $colName = $this->_orderByCast($colName);
                $this->getDb()->orderBy($colName, $order);
            }
    }

    /**
     * @param $colName string
     * @return string
     */
    private function _orderByCast($colName)
    {
        $searchColName = str_replace("{$this->getTableAlias()}.", '', $colName);
        if (in_array($searchColName, $this->scalarFields)) {
            return $this->getDb()->orderByCast($colName);
        }
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

    # SELECT

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
            foreach ($this->fields as $field) {
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
        if (empty($this->getSelect())) {
            $this->select($this->getTableFields());
        }
        return $this;
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

    # JOIN

    public function with($with)
    {
        if (is_array($with))
            foreach ($with as $item)
                $this->setWith($item);
        else
            $this->setWith($with);

        return $this;
    }

    public function setWith($with)
    {
        if (!$relationParams = $this->relations[$with]) {
            throw new ModelException($this->msg->i18n('Relation "%relation" not declared in class: ' . get_called_class(), array('relation' => $with)));
        }
        $relType = $relationParams[1];
        $relationClassName = ucfirst(str_replace('_', '', $relType)) . 'Relation';
        if (!$relation = $this->get($relationClassName, array(
            'modelName' => $relationParams[0],
            'type' => $relationParams[1],
            'linkKey' => $relationParams[2],
            'relationKeys' => $relationParams[3],
        ))) {
            throw new ModelException($this->msg->i18n('Relation "%relation" not declared in class: ' . get_called_class(), array('relation' => $relType)));
        }
        $this->relationClasses[$with] = $relation;

        $this->with[] = $with;

        return $this;
    }

    public function getWith()
    {
        return $this->with;
    }

    /**
     * очищает массив связанных моделей
     */
    public function clearWith()
    {
        $this->with = $this->relationClasses = array();
    }

    /**
     * @param $join array
     * @return array
     */
    private function _getJoin($join)
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        if (in_array($relation[1], array('has_many', 'has_one'))) {
            $joinModel = $this->get($relation[0]);
            return array($joinModel::getTableName() => $join[$relationName]);
        }
        throw new ModelException($this->msg->i18n('Determine the join condition of the table %table', array('table' => $join)));
    }

    /**
     * @param $join array
     * @return string
     */
    private function _getJoinCond($join)
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        $joinModel = $this->get($relation[0]);
        $tableName = static::$tableName;
        // алиасим имя таблицы
        if (!is_null($this->tableAlias))
            $tableName .= " AS {$this->tableAlias}";

        if (in_array($relation[1], array('has_many', 'has_one'))) {
            $on = $tableName . "." . $this->pkName . "=" . $join[$relationName] . "." . $relation[2];
            return $on;
        }
        throw new ModelException($this->msg->i18n('Determine the join condition of the table %table', array('table' => $join)));
    }

    public function joinRelation($join)
    {
        $on = $this->_getJoinCond($join);
        $join = $this->_getJoin($join);
        return $this->leftJoin($join, $on); 
    }

    # JOIN шорткаты

    public function join($join, $on=array(), $tblName=null)
    {
        return $this->leftJoin($join, $on, $tblName);
    }

    public function innerJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->innerJoin($join, $on, $tblName);
        return $this; 
    }

    public function leftJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->leftJoin($join, $on, $tblName);
        return $this; 
    }

    public function rightJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->rightJoin($join, $on, $tblName);
        return $this; 
    }

    public function outerJoin($join, $on=array(), $tblName=null)
    {
        if (is_null($tblName))
            $tblName = $this->getTableAlias();

        $this->join->outerJoin($join, $on, $tblName);
        return $this; 
    }

    /**
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
        $pk = $this->pkName;
        if (is_array($pk)) {
            foreach ($pk as $key)
                if (!in_array($key, $modelFields)) 
                    $modelFields[] = $key;
        } elseif (!in_array($pk, $modelFields)) 
            $modelFields[] = $pk;
            
        $this->select($modelFields);
    }

    # ГЕТТЕРЫ И СЕТТЕРЫ

    /**
     * Возвращает название аттрибута модели
     * 
     * @param $key string 
     * @return array
     */
    public function getAttributeName($key)
    {
        if (!$this->attributeNames) {
            $this->attributeNames = $this->fields;
        }
        return parent::getAttributeName($key);
    }

    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param string $singOrPlur число как грамматическая категория
     * @return string
     */
    public function getEntityName($singOrPlur)
    {
        return $this->entityNames[$singOrPlur] ?? null;
    }

    /**
     * fieldsList
     * список полей таблицы ч/з запятую
     * @return string
     */
    protected function getFieldsList()
    {
        $fields = $this->alias->aliasFields($this->fields, static::$tableName);
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
     * @return string
     */
    public function getTableAlias()
    {
        return $this->tableAlias ?? static::$tableName;
    }

    /**
     * возвращает список полей таблицы
     */
    public function getTableFields()
    {
        return $this->fields;
    }

    /**
     * возвращает имя первичного ключа
     * @return string
     */
    public function getPkName()
    {
        return $this->pkName;
    }

    /**
     * возвращает первичный ключ, приведенный в одну форму
     */
    public function getPkArr()
    {
        if (!$pkName = $this->pkName) {
            throw new ModelException($this->get('msg')->i18n('The primary key of the related table is not declared.'));
        }
        if (is_array($pkName)) {
            return $pkName;
        }
        return array($pkName);
    }

    /**
     * возвращает значение первичного ключа
     */
    public function getPk()
    {
        return $this->{$this->pkName};
    }

    public function getIsNew()
    {
        return $this->isNew;
    }

    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
        return $this;
    }

    public function getSelectFields()
    {
        return $this->selectFields;
    }

    public function setSelectFields($selectFields)
    {
        $this->selectFields = $selectFields;
        return $this;
    }

    /**
     * Шорткат
     * @return \tachyon\db\dbal\Db
     */
    public function getDb()
    {
        return $this->dbFactory->getDb();
    }

    public function getJoin()
    {
        return $this->join;
    }
}
