<?php

namespace tachyon\db\activeRecord;

use ReflectionException;
use tachyon\cache\Db as DbCache;
use tachyon\components\Message;
use tachyon\db\Query;
use tachyon\Model;

use tachyon\exceptions\{
    ContainerException,
    DBALException,
    ModelException,
    ValidationException,
};
use tachyon\helpers\{
    ClassHelper,
    StringHelper,
    DateTimeHelper,
};
use tachyon\db\Alias;
use tachyon\db\dbal\conditions\Terms;
use tachyon\db\dbal\{
    Db,
    DbFactory,
};

/**
 * @author imndsu@gmail.com
 */
abstract class ActiveRecord extends Model
{
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
     * MODELS RELATED VIA FOREIGN KEYS
     * 'has_one': relation "one-to-one" of type
     * ['object name' => [
     *      'external model',
     *      'has_one',
     *      'foreign key of this table, pointing to primary key of external table',
     *      array(list of fields of interest)(optional, if omitted, all fields declared in property are selected
     * $attributes of external model)
     * ]];
     * 'has_many': relation "one-to-many" of type
     * ['object name' => [
     *      'external model',
     *      'has_many',
     *      'foreign key of external table, pointing to primary key of this table',
     *      array(list of fields of interest) (optional, if omitted, all fields declared in property are selected
     * $attributes of external model)
     * ]];
     * 'many_to_many': relation "many-to-many" of type
     * ['object name' => [
     *      'external model',
     *      'many_to_many',
     *      array(
     *          'relation model', 'foreign key of junction table pointing to primary key of the model table',
     * 'foreign key of junction table pointing to primary key of external table', array(list of fields of interest
     * fields of junction table)(optional)
     *      )
     *      array(list of fields of interest)(optional, if omitted, all fields declared in property are selected
     * $attributes of external model)
     * ]];
     * 'join': subquery "one-to-many" relation of type
     * ['object name' => [
     *      'external model',
     *      'join',
     *      'foreign key of external table, pointing to primary key of this table',
     *      array(list of fields of interest) (optional, if omitted, all fields declared in property are selected
     * $attributes of external model)
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
        protected Message $msg,
        protected DbCache $cache,
        protected Alias $alias,
        protected DbFactory $dbFactory,
        protected Join $join,
        protected Terms $terms,
        protected Query $query,
        ...$params
    ) {
        // dependencies
        $this->alias->setOwner($this);
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
        // TODO: move to Relations
        $container = app();
        if (isset($this->relations[$var])) {
            // add external keys to declared connections
            $relationParams = $this->relations[$var];
            $relationModel = $container->get($this->getFullModelName($relationParams[0]));
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
                    $this->query->addJoin(
                        "{$relModel->getSource()} AS $relTableName",
                        "$relTableName.$linkKey=$thisTableName.$pk"
                    );
                    $this->query->setFields($relationParams[3]);
                    return $this->getDb()->selectOne(
                        $this->query,
                        $thisTableName,
                        [ "$thisTableName.$pk" => $this->$pk ]
                    );

                default:
                    break;
            }
        }
        return parent::__get($var);
    }

    ##################################
    #                                #
    #  TABLE ROW SELECTION METHODS  #
    #                                #
    ##################################

    /**
     * All records as arrays
     *
     * @param array $conditions search conditions array [field => value]
     *
     * @throws DBALException
     */
    public function findAllRaw(array $conditions = []): array
    {
        if (!empty($conditions)) {
            $this->addWhere($conditions);
        }
        $this->setDefaultSortBy();
        // set array of fields for selection
        $this->setSelect();
        // add foreign key(s) if not present in the selection fields array
        $this->addPkToSelect();
        // fields of this model (excluding those attached via JOIN)
        $modelFields = $this->getSelect();
        $tableName = static::$tableName;
        // alias fields and append to selection fields array
        $this->selectFields = array_merge($this->selectFields, $this->alias->aliasFields($modelFields, $tableName));
        // set fields for selection
        $this->select($this->selectFields);
        $this->alias->prependTableNameOnWhere($tableName, $this->getWhere());
        // alias table name
        if ($this->tableAlias !== '') {
            $tableName .= " AS {$this->tableAlias}";
        }
        // select records
        $items = $this->getDb()->select($this->query, $tableName);
        $this->clearSelect();
        $this->clearAlias();
        return $items;
    }

    /**
     * @throws DBALException
     */
    public function findOneRaw(array $conditions = []): ?array
    {
        $this->query->setLimit(1);
        if ($items = $this->findAllRaw($conditions)) {
            return $items[0];
        }
        return null;
    }

    /**
     * Returns row set by condition
     *
     * @throws ContainerException
     * @throws DBALException
     * @throws ReflectionException
     */
    public function findAll(array $conditions = []): array
    {
        // caching
        $cacheKey =
              json_encode($this->getTableName())
            . json_encode($this->getSelect())
            . json_encode($this->getWhere())
            . json_encode($this->getSortBy())
            . $this->getLimit()
            . $this->getGroupBy()
            . json_encode($this->with);
        if ($items = $this->cache->start($cacheKey)) {
            return json_decode($items);
        }
        if (!empty($conditions)) {
            $this->addWhere($conditions);
        }
        $this->setDefaultSortBy();
        // for table names aliasing in conditions and groupings
        $tableAliases = [];
        // attach selection queries of external objects
        foreach ($this->with as $with) {
            $relation = $this->relationClasses[$with];
            $tableAliases[$relation->getTableName()] = $relation->getTableAlias();
            $this->selectFields = array_merge($this->selectFields, $relation->getFields());
            $relation->joinWith($this);
        }
        // fields of this model (excluding those attached via JOIN)
        // alias table names in the selection fields array specified by $this->select()
        $this->alias->aliasSelectTableNames($tableAliases);
        // set array of fields for selection
        $this->setSelect();
        // add foreign key(s) if not present in the selection fields array
        $this->addPkToSelect();
        // alias fields and append to selection fields array
        $tableName = static::$tableName;
        $modelFields = $this->getSelect();
        $this->selectFields = array_merge($this->selectFields, $this->alias->aliasFields($modelFields, $tableName));
        // set fields for selection
        $this->select($this->selectFields);
        // alias table names in groupBy
        $this->alias->aliasGroupByTableName($tableAliases);
        // alias table names in sortBy
        $this->alias->aliasSortByTableName($tableAliases);
        // alias table names in conditions
        $this->alias->aliasWhereTableNames($tableAliases);
        // Select records
        if (!$items = $this->getDb()->select($this->query, $tableName)) {
            return [];
        }
        $this->clearSelect();
        $retItems = [];
        $modelFieldsKeys = array_flip($this->alias->getAliases($modelFields));
        foreach ($items as $item) {
            /*
            // field transformation (including timestamp) // TODO: remove from here!!!
            $item = $this->convVals($item, $this->selectFields);
            */
            $itemPk = $item[$this->pkName];
            // in order not to overwrite main record data in case of JOIN
            if (!array_key_exists($itemPk, $retItems)) {
                // we take only the fields of this model (without those attached via JOIN)
                $model = app()->get(get_called_class());
                $model->with($this->with);
                $model->setAttributes(array_intersect_key($item, $modelFieldsKeys));
                $model->setAttribute($this->pkName, $itemPk);
                $retItems[$itemPk] = $model;
            }
            // attach external objects
            // fill external fields attached via JOIN
            foreach ($this->with as $with) {
                /** @var Relation $relation */
                $relation = $this->relationClasses[$with];
                // select values of external fields
                $relation->setValues($item);
                if (count($relation->getValues()) === 0) {
                    continue;
                }
                // remove suffixes from keys
                $relation->trimSuffixes($with);
                /*
                // field transformation (including timestamp) // TODO: remove from here
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
        $this->cache->end($items);

        return $items;
    }

    /**
     * transformation of field values (including timestamp)
     * // TODO: remove
     */
    /*private function convVals(array $selectedFields, array $relationFields): array
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
                            return DateTimeHelper::timestampToDateTime($val);
                        default:
                            return $val;
                    }
                },
                array_keys($selectedFields),
                array_values($selectedFields)
            )
        );
    }*/

    /**
     * Search records in accordance with specified model properties
     *
     * @throws ContainerException | ReflectionException | DBALException
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
     * @throws ContainerException | ReflectionException | DBALException
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
     * @throws ContainerException | ReflectionException | DBALException
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
    #  TABLE MANIPULATION METHODS  #
    #                                 #
    ###################################

    /**
     * Saves model to DB. Returns model $pk when inserting a row
     *
     * @param $validate boolean whether to perform validation
     *
     * @throws ContainerException | ReflectionException | DBALException | ValidationException
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
     * Saves set of model fields to DB
     *
     * @param array $attrs array [field => value]
     * @param boolean $validate whether to perform validation
     *
     * @throws ContainerException | ReflectionException | DBALException | ValidationException
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
     * Hook on model save event
     */
    protected function afterSave(): bool
    {
        return true;
    }

    /**
     * Inserts model to DB and returns model $pk
     *
     * @throws ContainerException | ReflectionException | DBALException
     */
    public function insert(): false | string
    {
        if (!$lastInsertId = $this->getDb()->insert(
            $this->query,
            static::$tableName,
            $this->attributes
        )) {
            return false;
        }
        return $this->{$this->pkName} = $lastInsertId;
    }

    /**
     * Saves model to DB
     *
     * @throws ContainerException | ReflectionException | DBALException
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
        return $this->getDb()->update(
            $this->query,
            static::$tableName,
            $this->attributes,
            $condition
        );
    }

    /**
     * deletes model from DB
     *
     * @throws DBALException
     */
    public function delete(): bool
    {
        return (null !== $pk = $this->{$this->pkName})
            && $this->getDb()->delete(
                $this->query,
                static::$tableName,
                [$this->pkName => $pk]
            );
    }

    /**
     * Deletes all models matching the conditions
     *
     * @param array $attrs array [field => value]
     *
     * @throws ContainerException | ReflectionException | DBALException
     */
    public function deleteAllByAttrs(array $attrs): bool
    {
        return $this->getDb()->delete($this->query, static::$tableName, $attrs);
    }

    /**
     * delete models related to current via has_many
     */
    public function deleteRelatedModels(string $relName): void
    {
        foreach ($this->$relName as $relModel) {
            $relModel->delete();
            unset($relModel);
        }
    }

    /**
     * Assigning values to model attributes
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
    # VARIOUS SELECTION CONDITIONS ARE SPECIFIED HERE #
    #                                          #
    ############################################

    # WHERE

    public function getWhere(): array
    {
        return $this->query->getWhere();
    }

    public function where($where): self
    {
        $this->query->setWhere($where);
        return $this;
    }

    public function addWhere($where): self
    {
        $this->query->addWhere($where);
        return $this;
    }

    /**
     * Sets search conditions for the model
     */
    public function setSearchConditions(array $conditions = []): self
    {
        $this->addWhere($conditions);
        return $this;
    }

    public function gt(
        array &$where,
        string $field,
        string $arrKey,
        bool $precise = false
    ): self
    {
        $this->query->addWhere($this->terms->gt($where, $field, $arrKey, $precise));
        return $this;
    }

    public function lt(array &$where, string $field, string $arrKey, bool $precise = false): self
    {
        $this->query->addWhere($this->terms->lt($where, $field, $arrKey, $precise));
        return $this;
    }

    /**
     * Sets the LIKE condition
     */
    public function like(array $where, string $field): self
    {
        $this->query->addWhere($this->terms->like($where, $field));
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
     * Adding sorting of selected records by field $sortBy
     *
     * @throws DBALException
     */
    public function sortBy(string $colName, string $order = 'ASC'): self
    {
        $colName = preg_replace('/[^a-zA-Z0-9_.\-`]/', '', $colName);
        if ($colName === '') {
            return $this;
        }
        $order = strtoupper(trim($order));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }
        $colName = $this->orderByCast($colName);
        $this->query->orderBy($colName, $order);
        return $this;
    }

    /**
     * Sorting selected records only by field $sortBy
     *
     * @throws DBALException
     */
    public function setSortBy(array $sortBy): self
    {
        $cleanSortBy = [];
        foreach ($sortBy as $colName => $order) {
            if (is_numeric($colName)) {
                $colName = $order;
                $order = 'ASC';
            }
            $colName = preg_replace('/[^a-zA-Z0-9_.\-`]/', '', $colName);
            if ($colName === '') {
                continue;
            }
            $order = strtoupper(trim($order));
            if (!in_array($order, ['ASC', 'DESC'], true)) {
                $order = 'ASC';
            }
            $cleanSortBy[$colName] = $order;
        }
        $this->query->setOrderBy($cleanSortBy);
        return $this;
    }

    public function getSortBy(): array
    {
        return $this->query->getOrderBy();
    }

    /**
     * Set array of fields for sorting by default
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
                $colName = $this->orderByCast($colName);
                $this->query->orderBy($colName, $order);
            }
        }
    }

    /**
     * @throws DBALException
     */
    private function orderByCast(string $colName): string
    {
        $searchColName = str_replace("{$this->getTableAlias()}.", '', $colName);
        if (in_array($searchColName, $this->scalarFields)) {
            return $this->getDb()->orderByCast($colName);
        }
        return $colName;
    }

    # LIMIT

    /**
     * Limiting the number of selected records
     */
    public function limit(int $limit, int $offset = null): self
    {
        $this->query->setLimit($limit, $offset);
        return $this;
    }

    public function getLimit(): string
    {
        return $this->query->getLimit();
    }

    # GROUP BY

    /**
     * Grouping selected records by field $fieldName
     */
    public function groupBy($fieldName): self
    {
        $this->query->setGroupBy($fieldName);
        return $this;
    }

    public function getGroupBy(): string
    {
        return $this->query->getGroupBy();
    }

    # SELECT

    /**
     * Sets fields for select
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
        $this->query->setFields($fieldNames);
        return $this;
    }

    /**
     * Set array of fields for selection
     */
    public function setSelect(): self
    {
        if (empty($this->getSelect())) {
            $this->select($this->getTableFields());
        }
        return $this;
    }

    /**
     * Fields for SELECT / UPDATE
     */
    public function getSelect(): array
    {
        return $this->query->getFields();
    }

    /**
     * Clears selection fields
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
    protected function setWith($with): self
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

    /**
     * Clears the array of related models
     */
    protected function clearWith(): void
    {
        $this->with = $this->relationClasses = [];
    }

    /**
     * @throws ModelException
     */
    private function getJoinCond(array $join): array
    {
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        $tableName = static::$tableName;
        // alias table name
        if ($this->tableAlias !== '') {
            $tableName .= " AS {$this->tableAlias}";
        }
        if (in_array($relation[1], ['has_many', 'has_one'])) {
            return [
                "$tableName.{$this->pkName}" => "{$join[$relationName]}.{$relation[2]}"
            ];
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
        $on = $this->getJoinCond($join);
        $relationName = $this->join->getRelationName($join);
        $relation = $this->relations[$relationName];
        if (in_array($relation[1], ['has_many', 'has_one'])) {
            $joinModel = app()->get($relation[0]);
            $join = [$joinModel::getTableName() => $join[$relationName]];
            return $this->leftJoin($join, $on);
        }
        throw new ModelException(
            t('Determine the join condition of the table %table', ['table' => $join])
        );
    }

    # JOIN shortcuts

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
     * Sets alias of the current (main) query table.
     */
    public function asa(string $alias): self
    {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * Clears table alias fields
     */
    public function clearAlias(): void
    {
        $this->tableAlias = '';
    }

    /**
     * Add foreign key(s) if not present
     * in the selection fields array
     */
    public function addPkToSelect(): void
    {
        // take the array of selection fields specified by $this->select()
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
     * Returns model attribute name
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
     * @param string $singleOrPlural number as grammatical category
     */
    public function getEntityName(string $singleOrPlural): ?string
    {
        return $this->entityNames[$singleOrPlural] ?? null;
    }

    /**
     * List of table fields separated by commas
     */
    protected function getFieldsList(): string
    {
        $fields = $this->alias->aliasFields($this->fields, static::$tableName);
        return implode(',', $fields);
    }

    /**
     * Returns DB table name
     */
    public static function getTableName(): string
    {
        return static::$tableName;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias ?: static::$tableName;
    }

    /**
     * Returns list of table fields
     */
    public function getTableFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns primary key name
     */
    public function getPkName(): string
    {
        return $this->pkName;
    }

    /**
     * Returns primary key normalized to single form
     *
     * @throws ModelException
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
     * Returns primary key value
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
     * Shortcut
     *
     * @throws ContainerException
     * @throws ReflectionException
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

    public function getAlias(): Alias
    {
        return $this->alias;
    }

    private function getFullModelName(string $modelName): string
    {
        if (strpos('\\', $modelName) !== false) {
            return $modelName;
        }
        return "app\\models\\$modelName";
    }
}
