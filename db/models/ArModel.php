<?php
namespace tachyon\db\models;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 * 
 * Класс модели Active Record
 */
abstract class ArModel extends TableModel
{
    use \tachyon\dic\DbCache;

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
    
    protected $relationClasses = array();

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // добавляем внешние ключи по объявленным связям
        // TODO: перенести в findAll()
        foreach ($this->relations as $with => &$relationParams) {
            $relationModel = \tachyon\dic\Container::getInstanceOf($relationParams[0]);
            // приделываем поля связанной таблицы (если не заданы в связи)
            if (!isset($relationParams[3]))
                $relationParams[3] = $relationModel->getTableFields();

            // приделываем первичные ключи связанной таблицы
            $relationParams[3] = array_merge($relationParams[3], $relationModel->getPrimKeyArr());
            if (get_parent_class($relationModel)==='\tachyon\TableModel') {
                // приделываем алиасы первичных ключей связанной таблицы
                $relationParams[3] = array_merge($relationParams[3], $relationModel->getAlias()->getPrimKeyAliasArr($with));
            }
        }
    }

    /**
     * @param string $singOrPlur число как грамматическая категория
     * @return string
     */
    public function getEntityName($singOrPlur)
    {
        if (isset($this->entityNames[$singOrPlur]))
            return $this->entityNames[$singOrPlur];
    }

    /**
     * Лениво извлекаем связанные записи
     * // TODO: перенести в Relations
     */
    public function __get($var)
    {
        // если это не подключенное ч/з with св-во ("жадная" загрузка)
        // ищем среди присоединенных ч/з внешние ключи объектов
        // TODO: перенести в Relations
        if (isset($this->relations[$var])) {
            $relationArr = $this->relations[$var];
            $relModel = \tachyon\dic\Container::getInstanceOf($relationArr[0]);
            $type = $relationArr[1];
            // первичный ключ внешней таблицы
            $relPk = $relModel->getPrimKey();
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
                    $thisPk = static::$primKey;
                    // внешний ключ связанной таблицы, указывающий на первичный ключ этой таблицы
                    $fk = $relationArr[2];
                    return $relModel
                        ->where(array($fk => $this->$thisPk))
                        ->findAll();
                break;

                case 'many_to_many':
                    $pk = static::$primKey;
                    $lnkModel = \tachyon\dic\Container::getInstanceOf($relationArr[2][0]);
                    $linkTableName = $lnkModel->getTableName();
                    $relFk1 = $relationArr[2][1];
                    $relFk2 = $relationArr[2][2];
                    $relTableName = $relModel->getTableName();
                    $thisTableName = $this->getTableName();
                    if (isset($relationArr[2][3]))
                        foreach ($relationArr[2][3] as $fieldName)
                            $relModel->select("$linkTableName.$fieldName");

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
                    $pk = static::$primKey;
                    $thisTableName = $this->getTableName();
                    $db = $this->getDb();
                    $db->setJoin("{$relModel->getSource()} AS $relTableName", "$relTableName.$linkKey=$thisTableName.$pk");
                    $db->setFields($relationArr[3]);
                    return $db->selectOne($thisTableName, array("$thisTableName.$pk" => $this->$pk));
                break;
                
                default: break;
            }
        }
        return parent::__get($var);
    }

    #################################
    #                               #
    #  МЕТОДЫ ВЫБОРКИ СТРОК ТАБЛИЦЫ #
    #                               #
    #################################

    /**
     * Возвращает набор строк по условию
     * 
     * @return array
     */
    public function findAll()
    {
        // подключаем компонент кеширования
        $cache = $this->getCache();
        // кеширование
        $cacheKey = json_encode($this->getTableName())
                  . json_encode($this->getSelect())
                  . json_encode($this->getWhere())
                  . json_encode($this->getSortBy())
                  . $this->getLimit()
                  . $this->getGroupBy()
                  . json_encode($this->with);

        if ($items = $cache->start($cacheKey))
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
        $this->getAlias()->aliasSelectTableNames($tableAliases, $this);
        // устанавливаем массив полей для выборки
        $this->setSelect();
        // добавляем внешний(е) ключ(и) если его(их) нет в массиве полей для выборки
        $this->addPkToSelect();
        // алиасим поля и присобачиваем к массиву полей для выборки
        $tableName = static::$tableName;
        $modelFields = $this->getSelect();
        $this->selectFields = array_merge($this->selectFields, $this->getAlias()->aliasFields($modelFields, $tableName));
        // устанавливаем поля для выборки
        $this->select($this->selectFields);
        // алиасим имена таблиц в groupBy
        $this->getAlias()->aliasGroupByTableName($tableAliases, $this);
        // алиасим имена таблиц в sortBy
        $this->getAlias()->aliasSortByTableName($tableAliases, $this);
        // алиасим имена таблиц в условиях
        $this->getAlias()->aliasWhereTableNames($tableAliases, $this);

        // ВЫБИРАЕМ ЗАПИСИ
        $items = $this->getDb()->select($tableName);
        $this->clearSelect();

        $retItems = array();

        $modelName = \tachyon\helpers\StringHelper::getShortClassName(get_called_class());
        $modelFieldsKeys = array_flip($this->getAlias()->getAliases($modelFields));
        foreach ($items as $item) {
            /*
            // преобразование полей (в т.ч. timestamp) // TODO: убрать отсюда!!!
            $item = $this->convVals($item, $this->selectFields);
            */
            $itemPk = $item[static::$primKey];
            // чтобы не перезаписывать данные основной записи в случае JOIN
            if (!array_key_exists($itemPk, $retItems)) {
                // берём только поля данной модели (без присоединенных ч/з JOIN)
                $model = \tachyon\dic\Container::getInstanceOf($modelName);
                $model->with($this->with);
                $model->setAttributes(array_intersect_key($item, $modelFieldsKeys));
                $model->setAttribute(static::$primKey, $itemPk);
                $retItems[$itemPk] = $model;
            }
            // приделываем внешние объекты
            // заполняем внешние поля, присоединенные ч/з JOIN
            foreach ($this->with as $with) {
                /** @var \tachyon\db\relations\Relation $relation */
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
                $relation->values = $this->convVals($relation->values, $attributeTypes);
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

        $cache->end($items);

        return $items;
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
     * @return \tachyon\db\models\ArModel
     */
    public function findByPk($pk, $fields=null)
    {
        $primKey = static::$primKey;
        if (is_array($primKey)) {
            $condition = array_combine($primKey, $pk);
        } elseif (is_string($primKey)) {
            $primKeyArr = $this->getAlias()->aliasFields(array($primKey), static::$tableName);
            $primKey = $primKeyArr[0];
            $condition = array($primKey => $pk);
        }
        $this->where($condition);
        return $this->findOne($fields);
    }

    /**
     * findOneByAttrs
     * shortcut
     * @return \tachyon\db\models\ArModel
     */
    public function findOneByAttrs(array $attrs=array(), $fields=null)
    {
        $this->addWhere($attrs);
        return $this->findOne($fields);
    }

    /**
     * findOne
     * shortcut
     * @return \tachyon\db\models\ArModel
     */
    public function findOne($fields=null)
    {
        if (!is_null($fields))
            $this->select($fields);

        if (!$items = $this->findAll())
            return null;

        $item = array_shift($items);
        $item->isNew = false;
        return $item;
    }

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
            throw new \Exception("Связь $with не объявлена в классе: " . get_called_class());
        }
        $relType = $relationParams[1];
        $relationClassName = ucfirst(str_replace('_', '', $relType)) . 'Relation';
        if (!$relation = \tachyon\dic\Container::getInstanceOf($relationClassName, array(
            'modelName' => $relationParams[0],
            'type' => $relationParams[1],
            'linkKey' => $relationParams[2],
            'relationKeys' => $relationParams[3],
        ))) {
            throw new \Exception("Связь $relType не объявлена в классе: " . get_called_class());
        }
        $this->relationClasses[$with] = $relation;

        $this->with[] = $with;
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
     * удаляем модели has_many
     */
    protected function delRelModels($relName)
    {
        foreach ($this->$relName as $relModel)
            $relModel->delete();
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
            $joinModel = \tachyon\dic\Container::getInstanceOf($relation[0]);
            return array($joinModel::$tableName => $join[$relationName]);
        }
        throw new \Exception("Определите условие присоединения таблицы $join");
    }

    /**
     * @param $join array
     * @return string
     */
    private function _getJoinCond($join)
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        $joinModel = \tachyon\dic\Container::getInstanceOf($relation[0]);
        $tableName = static::$tableName;
        // алиасим имя таблицы
        if (!is_null($this->tableAlias))
            $tableName .= " AS {$this->tableAlias}";

        if (in_array($relation[1], array('has_many', 'has_one'))) {
            $on = $tableName . "." . static::$primKey . "=" . $join[$relationName] . "." . $relation[2];
            return $on;
        }
        throw new \Exception("Определите условие присоединения таблицы $join");
    }

    public function joinRelation($join)
    {
        $on = $this->_getJoinCond($join);
        $join = $this->_getJoin($join);
        return $this->leftJoin($join, $on); 
    }

    public function getRelations()
    {
        return $this->relations;
    }
}
