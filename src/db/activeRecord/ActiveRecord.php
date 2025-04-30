<?php

namespace tachyon\db\activeRecord;

use ReflectionException;
use tachyon\{cache\Db as DbCache,
    components\Message,
    exceptions\ContainerException,
    exceptions\DBALException,
    exceptions\ModelException,
    exceptions\ValidationException,
    Helpers\ClassHelper,
    Helpers\StringHelper,
    Model,
    traits\DateTime};
use tachyon\db\{
    Alias,
    dbal\Db,
    dbal\DbFactory,
    Terms,
};

/**
 * @author imndsu@gmail.com
 */
abstract class ActiveRecord extends Model
{
    use DateTime;
    use Terms {
        Terms::gt as _gt;
        Terms::lt as _lt;
        Terms::lt as _like;
    }

    protected DbCache $cache;
    protected DbFactory $dbFactory;
    protected Alias $alias;
    protected Join $join;
    protected Message $msg;

    /**
     * the name of the table in the database or alias
     */
    protected static string $tableName = '';
    /**
     * the name of the primary key field
     */
    protected string $pkName = 'id';
    /**
     * table fields
     */
    protected array $fields = [];
    /**
     * selected fields
     */
    protected array $selectFields = [];
    /**
     * SQL types of table fields
     */
    protected array $fieldTypes = [];
    /**
     * Alias of the main request table
     */
    protected string $tableAlias = '';
    /**
     * The fields by which the table is sorted using ORDER BY CAST
     */
    protected array $scalarFields = [];
    /**
     * Fields by which the table is sorted by default
     */
    protected array $defSortBy = [];

    protected array $entityNames = [];

    /**
     * СВЯЗАННЫЕ Ч/З ВНЕШНИЕ КЛЮЧИ МОДЕЛИ
     * 'has_one': связь "один к одному" вида
     * ['название объекта' => [
     *      'внешняя модель',
     *      'has_one',
     *      'внешний ключ данной таблицы, указывающий на первичный ключ внешней таблицы',
     *      array(список интересующих полей)(optional, если пропущен, выбираются все поля, объявленные в св-ве
     * $attributes внешней модели)
     * ]];
     * 'has_many': связь "один ко многим" вида
     * ['название объекта' => [
     *      'внешняя модель',
     *      'has_many',
     *      'внешний ключ внешней таблицы, указывающий на первичный ключ данной таблицы',
     *      array(список интересующих полей) (optional, если пропущен, выбираются все поля, объявленные в св-ве
     * $attributes внешней модели)
     * ]];
     * 'many_to_many': связь "многие ко многим" вида
     * ['название объекта' => [
     *      'внешняя модель',
     *      'many_to_many',
     *      array(
     *          'модель связи', 'внешний ключ расшивочной таблицы, указывающий на первичный ключ таблицы модели',
     * 'внешний ключ расшивочной таблицы, указывающий на первичный ключ внешней таблицы', array(список интересующих
     * полей расшивочной таблицы)(optional)
     *      )
     *      array(список интересующих полей)(optional, если пропущен, выбираются все поля, объявленные в св-ве
     * $attributes внешней модели)
     * ]];
     * 'join': подзапрос связь "один ко многим" вида
     * ['название объекта' => [
     *      'внешняя модель',
     *      'join',
     *      'внешний ключ внешней таблицы, указывающий на первичный ключ данной таблицы',
     *      array(список интересующих полей) (optional, если пропущен, выбираются все поля, объявленные в св-ве
     * $attributes внешней модели)
     * ]];
     */
    protected array $relations = [];

    /**
     * Related models by which a selection occurs
     */
    protected array $with = [];

    /**
     * Marker: is it a new model or extracted from the database
     */
    protected bool $isNew = true;
    /**
     * Marker: Changed unsaved model
     * Not used yet
     */
    protected bool $isDirty = false;

    protected array $relationClasses = [];

    public function __construct(
        Message $msg,
        DbCache $cache,
        Alias $alias,
        DbFactory $dbFactory,
        Join $join,
        ...$params
    ) {
        // dependencies
        $this->msg = $msg;
        $this->cache = $cache;
        $this->alias = $alias;
        $this->alias->setOwner($this);
        $this->dbFactory = $dbFactory;
        $this->join = $join;
        $this->join->setOwner($this);

        if ('' === static::$tableName) {
            static::$tableName = StringHelper::camelToSnake(
                ClassHelper::getClassName(static::class)
            ) . 's';
        }

        parent::__construct(...$params);
    }

    /**
     * Lazily extract related records
     *
     * @throws ContainerException
     * @throws DBALException
     * @throws ModelException
     * @throws ReflectionException
     */
    public function __get($var): array | string | self | null
    {
        // If this is not connected through the "with" property (greedy loading),
        // we look among the objects connected through external keys
        // TODO: перенести в Relations
        $container = app();
        if (isset($this->relations[$var])) {
            // add external keys to declared connections
            $relationParams = $this->relations[$var];
            $relationModel = $container->get($this->_getFullModelName($relationParams[0]));
            // attach the fields of the related table (if not specified in connection)
            if (!isset($relationParams[3])) {
                $relationParams[3] = $relationModel->getTableFields();
            }
            // attach the primary keys of the related table
            $relationParams[3] = array_merge($relationParams[3], $relationModel->getPkArr());
            if ($relationModel instanceof self) {
                // attach the aliases of primary keys of the related table
                $relationParams[3] = array_merge(
                    $relationParams[3],
                    $relationModel
                        ->getAlias()
                        ->getPrimKeyAliasArr(
                            $this->with,
                            $relationModel->getPkName()
                        )
                );
            }
            /** @var self $relModel */
            $relModel = $container->get("\\app\\models\\$relationParams[0]");
            $type = $relationParams[1];
            // primary key of the external table
            $relPk = $relModel->getPkName();
            switch ($type) {
                case 'has_one':
                    $fk = $relationParams[2];
                    $where = is_array($fk) ? [$fk[1] => $this->{$fk[0]}] : [$relPk => $this->$fk];
                    return $relModel
                        ->where($where)
                        ->findOne();

                case 'has_many':
                    // primary key of the main table
                    $thisPk = $this->pkName;
                    // the related table external key pointing to the primary key of the main table
                    $fk = $relationParams[2];
                    return $relModel
                        ->where([$fk => $this->$thisPk])
                        ->findAll();

                case 'many_to_many':
                    $pk = $this->pkName;
                    $lnkModel = $container->get($relationParams[2][0]);
                    $linkTableName = $lnkModel->getTableName();
                    $relFk1 = $relationParams[2][1];
                    $relFk2 = $relationParams[2][2];
                    $relTableName = $relModel->getTableName();
                    $thisTableName = $this->getTableName();
                    if (isset($relationParams[2][3])) {
                        foreach ($relationParams[2][3] as $fieldName) {
                            $relModel->select("$linkTableName.$fieldName");
                        }
                    }
                    return $relModel
                        ->join($linkTableName, "$linkTableName.$relFk2 = $relTableName.$relPk")
                        ->join($thisTableName, "$linkTableName.$relFk1 = $thisTableName.$pk")
                        ->where(["$thisTableName.$pk" => $this->$pk])
                        ->findAll();

                case 'belongs_to':
                    // external key of main table pointing to the primary key of the external table
                    $relFk = $relationParams[2];
                    return $relModel
                        ->where([$relPk => $this->$relFk])
                        ->findOne();

                case 'join':
                    $linkKey = $relationParams[2];
                    $relTableName = $relModel->getTableName();
                    $pk = $this->pkName;
                    $thisTableName = $this->getTableName();
                    $this->getDb()->addJoin(
                        "{$relModel->getSource()} AS $relTableName",
                        "$relTableName.$linkKey=$thisTableName.$pk"
                    );
                    $this->getDb()->setFields($relationParams[3]);
                    return $this->getDb()->selectOne($thisTableName, ["$thisTableName.$pk" => $this->$pk]);

                default:
                    break;
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
     *
     * @param array $conditions условия поиска массив [поле => значение]
     *
     * @throws DBALException
     */
    public function findAllRaw(array $conditions = []): array
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
        if ($this->tableAlias !== '') {
            $tableName .= " AS {$this->tableAlias}";
        }
        // выбираем записи
        $items = $this->getDb()->select($tableName);
        $this->clearSelect();
        $this->clearAlias();
        return $items;
    }

    /**
     * @throws DBALException
     */
    public function findOneRaw(array $conditions = []): ?array
    {
        $this->getDb()->setLimit(1);
        if ($items = $this->findAllRaw($conditions)) {
            return $items[0];
        }
        return null;
    }

    /**
     * Возвращает набор строк по условию
     *
     * @throws ContainerException
     * @throws DBALException
     * @throws ReflectionException
     */
    public function findAll(array $conditions = []): array
    {
        // кеширование
        $cacheKey =
            json_encode($this->getTableName())
            . json_encode($this->getSelect())
            . json_encode($this->getWhere())
            . json_encode($this->getSortBy())
            . $this->getLimit()
            . $this->getGroupBy()
            . json_encode($this->with);
        if ($items = $this->cache->start($cacheKey)) {
            return $items;
        }
        if (!empty($conditions)) {
            $this->addWhere($conditions);
        }
        $this->setDefaultSortBy();
        // для алиасинга имен таблиц в условиях и группировках
        $tableAliases = [];
        // приделываем запросы выборки внешних объектов
        foreach ($this->with as $with) {
            $relation = $this->relationClasses[$with];
            $tableAliases[$relation->getTableName()] = $relation->getTableAlias();
            $this->selectFields = array_merge($this->selectFields, $relation->getFields());
            $relation->joinWith($this);
        }
        // поля данной модели (без присоединенных ч/з JOIN)
        // алиасим имена таблиц в массиве полей для выборки, заданный $this->select()
        $this->alias->aliasSelectTableNames($tableAliases);
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
        $this->alias->aliasGroupByTableName($tableAliases);
        // алиасим имена таблиц в sortBy
        $this->alias->aliasSortByTableName($tableAliases);
        // алиасим имена таблиц в условиях
        $this->alias->aliasWhereTableNames($tableAliases);
        // ВЫБИРАЕМ ЗАПИСИ
        if (!$items = $this->getDb()->select($tableName)) {
            return [];
        }
        $this->clearSelect();
        $retItems = [];
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
                $model = app()->get(get_called_class());
                $model->with($this->with);
                $model->setAttributes(array_intersect_key($item, $modelFieldsKeys));
                $model->setAttribute($this->pkName, $itemPk);
                $retItems[$itemPk] = $model;
            }
            // приделываем внешние объекты
            // заполняем внешние поля, присоединенные ч/з JOIN
            foreach ($this->with as $with) {
                /** @var Relation $relation */
                $relation = $this->relationClasses[$with];
                // выбираем значения внешних полей
                $relation->setValues($item);
                if (count($relation->getValues()) === 0) {
                    continue;
                }
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
    private function _convVals(array $selectedFields, array $relationFields): array
    {
        if (count($selectedFields) === 0) {
            return $selectedFields;
        }
        return array_combine(
            array_keys($selectedFields),
            array_map(
                function ($key, $val) use ($relationFields) {
                    if (!isset($relationFields[$key])) {
                        return $val;
                    }
                    $fieldType = $relationFields[$key];
                    switch ($fieldType) {
                        case 'timestamp':
                            return $this->timestampToDateTime($val);
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
     * Поиск записей в соотв с заданными св-вами модели
     *
     * @throws DBALException
     */
    public function search($fields = null): array
    {
        $where = [];
        $tableName = self::getTableName();
        foreach ($this->attributes as $key => $value) {
            if (!is_null($value)) {
                $where["$tableName.$key"] = $value;
            }
        }
        $this->where($where);
        if (!is_null($fields)) {
            $this->select($fields);
        }
        return $this->findAll();
    }

    /**
     * shortcut
     *
     * @throws DBALException
     */
    public function findByPk(int | string $pk): ?self
    {
        $pkName = $this->pkName;
        if (is_array($pkName)) {
            $conditions = array_combine($pkName, $pk);
        } elseif (is_string($pkName)) {
            $primKeyArr = $this->alias->aliasFields([$pkName], static::$tableName);
            $pkName = $primKeyArr[0];
            $conditions = [$pkName => $pk];
        }
        return $this->findOne($conditions);
    }

    /**
     * @throws DBALException
     */
    public function findOne(array $conditions = []): ?self
    {
        if ($items = $this
            ->limit(1)
            ->findAll($conditions)
        ) {
            $item = $items[0];
            $item->isNew = false;
            return $item;
        }
        return null;
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
     *
     * @throws DBALException
     * @throws ValidationException
     */
    public function save(bool $validate = true): false | int | string
    {
        if ($validate && !$this->validate()) {
            return false;
        }
        $result = $this->isNew ? $this->insert() : $this->update();
        if ($result !== false) {
            $this->afterSave();
        }
        return $result;
    }

    /**
     * Сохраняет набор полей модели в БД
     *
     * @param array $attrs массив [поле => значение]
     * @param boolean $validate производить ли валидацию
     *
     * @throws DBALException
     * @throws ValidationException
     */
    public function saveAttrs(array $attrs, bool $validate = false): false | int
    {
        $this->setAttributes($attrs);
        if ($validate && !$this->validate(array_keys($attrs))) {
            return false;
        }
        return $this->update();
    }

    /**
     * Хук на событие сохранения модели
     */
    protected function afterSave(): bool
    {
        return true;
    }

    /**
     * вставляет модель в БД возвращает $pk модели
     *
     * @throws DBALException
     */
    public function insert(): false | string
    {
        if (!$lastInsertId = $this->getDb()->insert(static::$tableName, $this->attributes)) {
            return false;
        }
        return $this->{$this->pkName} = $lastInsertId;
    }

    /**
     * Сохраняет модель в БД
     *
     * @throws DBALException
     */
    public function update(): int
    {
        $condition = [];
        $pk = $this->pkName;
        if (is_array($pk)) {
            foreach ($pk as $key) {
                $condition[$key] = $this->$key;
            }
        } else {
            $condition[$pk] = $this->$pk;
        }
        return $this->getDb()->update(static::$tableName, $this->attributes, $condition);
    }

    /**
     * удаляет модель из БД
     *
     * @throws DBALException
     */
    public function delete(): bool
    {
        return (null !== $pk = $this->{$this->pkName})
            && $this->getDb()->delete(
                static::$tableName,
                [$this->pkName => $pk]
            );
    }

    /**
     * Удаляет все модели, соотв. условиям
     *
     * @param array $attrs массив [поле => значение]
     *
     * @throws DBALException
     */
    public function deleteAllByAttrs(array $attrs): bool
    {
        return $this->getDb()->delete(static::$tableName, $attrs);
    }

    /**
     * удаляем модели, связанные с текущей ч/з has_many
     */
    public function deleteRelatedModels(string $relName): void
    {
        foreach ($this->$relName as $relModel) {
            $relModel->delete();
            unset($relModel);
        }
    }

    /**
     * Присваивание значений аттрибутам модели
     */
    public function setAttributes(array $attributes): Model
    {
        foreach ($attributes as $name => $value) {
            if (in_array($name, $this->fields, true)) {
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

    /**
     * @throws DBALException
     */
    public function getWhere(): array
    {
        return $this->getDb()->getWhere();
    }

    /**
     * @throws DBALException
     */
    public function where($where): self
    {
        $this->getDb()->setWhere($where);
        return $this;
    }

    /**
     * @throws DBALException
     */
    public function addWhere($where): self
    {
        $this->getDb()->addWhere($where);
        return $this;
    }

    /**
     * Устанавливает условия поиска для модели
     */
    public function setSearchConditions(array $conditions = []): self
    {
        $this->addWhere($conditions);
        return $this;
    }

    /**
     * @throws DBALException
     */
    public function gt(array &$where, string $field, string $arrKey, bool $precise = false): self
    {
        $this->getDb()->addWhere($this->_gt($where, $field, $arrKey, $precise));
        return $this;
    }

    /**
     * @throws DBALException
     */
    public function lt(array &$where, string $field, string $arrKey, bool $precise = false): self
    {
        $this->getDb()->addWhere($this->_lt($where, $field, $arrKey, $precise));
        return $this;
    }

    /**
     * Устанавливает условие LIKE
     *
     * @throws DBALException
     */
    public function like(array $where, string $field): self
    {
        $this->getDb()->addWhere($this->_like($where, $field));
        return $this;
    }

    # ORDER BY

    public function setSortConditions($attrs): self
    {
        if (isset($attrs['order'])) {
            $this->sortBy($attrs['field'], $attrs['order']);
        }
        return $this;
    }

    /**
     * добавление сортировки выбранных записей по полю $sortBy
     *
     * @throws DBALException
     */
    public function sortBy(string $colName, string $order = 'ASC'): self
    {
        $colName = $this->_orderByCast($colName);
        $this->getDb()->orderBy($colName, $order);
        return $this;
    }

    /**
     * сортировка выбранных записей только по полю $sortBy
     *
     * @throws DBALException
     */
    public function setSortBy(array $sortBy): self
    {
        $this->getDb()->setOrderBy($sortBy);
        return $this;
    }

    public function getSortBy(): array
    {
        return $this->getDb()->getOrderBy();
    }

    /**
     * устанавливаем массив полей для сортировки по умолчанию
     * @throws DBALException
     */
    protected function setDefaultSortBy(): void
    {
        if (empty($this->getSortBy())) {
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
    }

    /**
     * @throws DBALException
     */
    private function _orderByCast(string $colName): string
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
     *
     * @throws DBALException
     */
    public function limit(int $limit, int $offset = null): self
    {
        $this->getDb()->setLimit($limit, $offset);
        return $this;
    }

    /**
     * @throws DBALException
     */
    public function getLimit(): string
    {
        return $this->getDb()->getLimit();
    }

    # GROUP BY

    /**
     * группировка выбранных записей по полю $fieldName
     *
     * @throws DBALException
     */
    public function groupBy($fieldName): self
    {
        $this->getDb()->setGroupBy($fieldName);
        return $this;
    }

    /**
     * @throws DBALException
     */
    public function getGroupBy(): string
    {
        return $this->getDb()->getGroupBy();
    }

    # SELECT

    /**
     * задает поля для селекта
     *
     * @throws DBALException
     */
    public function select(): self
    {
        $fieldNames = func_get_args();
        if (is_array($fieldNames[0])) {
            $fieldNames = $fieldNames[0];
        }
        $all = false;
        foreach ($fieldNames as $key => $fieldName) {
            if (!is_numeric($key)) {
                $fieldNames[] = "$key AS $fieldName";
                unset($fieldNames[$key]);
            }
            if ($fieldName === '*') {
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
    public function setSelect(): self
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
    public function getSelect(): array
    {
        return $this->getDb()->getFields();
    }

    /**
     * очищает поля выборки
     */
    public function clearSelect(): void
    {
        $this->selectFields = [];
    }

    # JOIN

    /**
     * @throws ContainerException
     * @throws ModelException
     * @throws ReflectionException
     */
    public function with($with): self
    {
        if (is_array($with)) {
            foreach ($with as $item) {
                $this->setWith($item);
            }
        } else {
            $this->setWith($with);
        }
        return $this;
    }

    /**
     * @throws ModelException
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function setWith($with): self
    {
        if (!$relationParams = $this->relations[$with]) {
            throw new ModelException(
                t(
                    'Relation "%relation" not declared in class: ' . get_called_class(),
                    ['relation' => $with]
                )
            );
        }
        $relType = $relationParams[1];
        $relationClassName = ucfirst(str_replace('_', '', $relType)) . 'Relation';
        if (!$relation = app()->get(
            $relationClassName,
            [
                'modelName' => $relationParams[0],
                'type' => $relationParams[1],
                'linkKey' => $relationParams[2],
                'relationKeys' => $relationParams[3],
            ]
        )) {
            throw new ModelException(
                t(
                    'Relation "%relation" not declared in class: ' . get_called_class(),
                    ['relation' => $relType]
                )
            );
        }
        $this->relationClasses[$with] = $relation;
        $this->with[] = $with;
        return $this;
    }

    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * очищает массив связанных моделей
     */
    public function clearWith(): void
    {
        $this->with = $this->relationClasses = [];
    }

    /**
     * @throws ContainerException
     * @throws ModelException
     * @throws ReflectionException
     */
    private function _getJoin(array $join): array
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        if (in_array($relation[1], ['has_many', 'has_one'])) {
            $joinModel = app()->get($relation[0]);
            return [$joinModel::getTableName() => $join[$relationName]];
        }
        throw new ModelException(
            t('Determine the join condition of the table %table', ['table' => $join])
        );
    }

    /**
     * @throws ModelException
     */
    private function _getJoinCond(array $join): string
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        $tableName = static::$tableName;
        // алиасим имя таблицы
        if ($this->tableAlias !== '') {
            $tableName .= " AS {$this->tableAlias}";
        }
        if (in_array($relation[1], ['has_many', 'has_one'])) {
            return "$tableName.{$this->pkName}={$join[$relationName]}.{$relation[2]}";
        }
        throw new ModelException(
            t('Determine the join condition of the table %table', ['table' => $join])
        );
    }

    /**
     * @throws ContainerException
     * @throws ModelException
     * @throws ReflectionException
     */
    public function joinRelation($join): self
    {
        $on = $this->_getJoinCond($join);
        $join = $this->_getJoin($join);
        return $this->leftJoin($join, $on);
    }

    # JOIN шорткаты

    public function join($join, array $on = [], string $tblName = null): self
    {
        return $this->leftJoin($join, $on, $tblName);
    }

    public function innerJoin($join, array $on = [], string $tblName = null): self
    {
        if (is_null($tblName)) {
            $tblName = $this->getTableAlias();
        }
        $this->join->innerJoin($join, $on, $tblName);
        return $this;
    }

    public function leftJoin($join, array $on = [], string $tblName = null): self
    {
        if (is_null($tblName)) {
            $tblName = $this->getTableAlias();
        }
        $this->join->leftJoin($join, $on, $tblName);
        return $this;
    }

    public function rightJoin($join, array $on = [], string $tblName = null): self
    {
        if (is_null($tblName)) {
            $tblName = $this->getTableAlias();
        }
        $this->join->rightJoin($join, $on, $tblName);
        return $this;
    }

    public function outerJoin($join, array $on = [], string $tblName = null): self
    {
        if (is_null($tblName)) {
            $tblName = $this->getTableAlias();
        }
        $this->join->outerJoin($join, $on, $tblName);
        return $this;
    }

    /**
     * Устанавливает алиас текущей (главной) таблицы запроса.
     */
    public function asa(string $alias): self
    {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * очищает поля алиас таблицы
     */
    public function clearAlias(): void
    {
        $this->tableAlias = '';
    }

    /**
     * добавляем внешний(е) ключ(и) если его(их)
     * нет в массиве полей для выборки
     */
    public function addPkToSelect(): void
    {
        // берем массив полей для выборки, заданный $this->select()
        $modelFields = $this->getSelect();
        $pk = $this->pkName;
        if (is_array($pk)) {
            foreach ($pk as $key) {
                if (!in_array($key, $modelFields, true)) {
                    $modelFields[] = $key;
                }
            }
        } elseif (!in_array($pk, $modelFields, true)) {
            $modelFields[] = $pk;
        }
        $this->select($modelFields);
    }

    # getters and setters

    /**
     * Возвращает название аттрибута модели
     */
    public function getAttributeName(string $key): string
    {
        if (!$this->attributeNames) {
            $this->attributeNames = $this->fields;
        }
        return parent::getAttributeName($key);
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param string $singleOrPlural число как грамматическая категория
     */
    public function getEntityName(string $singleOrPlural): ?string
    {
        return $this->entityNames[$singleOrPlural] ?? null;
    }

    /**
     * список полей таблицы ч/з запятую
     *
     * @return string
     */
    protected function getFieldsList(): string
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
    public function getTableAlias(): string
    {
        return $this->tableAlias ?: static::$tableName;
    }

    /**
     * возвращает список полей таблицы
     */
    public function getTableFields(): array
    {
        return $this->fields;
    }

    /**
     * возвращает имя первичного ключа
     *
     * @return string
     */
    public function getPkName(): string
    {
        return $this->pkName;
    }

    /**
     * Возвращает первичный ключ, приведенный в одну форму
     *
     * @return array
     * @throws ContainerException
     * @throws ModelException
     * @throws ReflectionException
     */
    public function getPkArr(): array
    {
        if (!$pkName = $this->pkName) {
            throw new ModelException(
                t('The primary key of the related table is not declared.')
            );
        }
        return (array)($pkName);
    }

    /**
     * возвращает значение первичного ключа
     */
    public function getPk()
    {
        return $this->{$this->pkName};
    }

    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew($isNew): self
    {
        $this->isNew = $isNew;
        return $this;
    }

    public function getSelectFields(): array
    {
        return $this->selectFields;
    }

    public function setSelectFields($selectFields): self
    {
        $this->selectFields = $selectFields;
        return $this;
    }

    /**
     * Шорткат
     *
     * @return Db
     * @throws DBALException
     */
    public function getDb(): Db
    {
        return $this->dbFactory->getDb();
    }

    public function getJoin(): Join
    {
        return $this->join;
    }

    /**
     * @return Alias
     */
    public function getAlias(): Alias
    {
        return $this->alias;
    }

    /**
     * @param string $modelName
     *
     * @return string
     */
    private function _getFullModelName($modelName): string
    {
        if (strpos('\\', $modelName) !== false) {
            return $modelName;
        }
        return "app\\models\\$modelName";
    }
}
