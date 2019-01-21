<?php
namespace tachyon\db\dbal;

use tachyon\exceptions\DataBaseException;

/**
 * DBAL (на PDO)
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Db extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Message;

    /**
     * параметры БД
     */
    protected $config;
    /**
     * соединение с БД (PDO)
     */
    protected $connection;
    /**
     * Компонент msg
     */
    protected $msg;
    /**
     * Выводить ли анализ запросов в файл
     */
    protected $explain;
    /**
     * поля для выборки/вставки/обновления
     */
    protected $fields = array();
    /**
     * условия для выборки
     */
    protected $where = array();
    protected $join = '';
    protected $groupBy = '';
    protected $orderBy = array();
    protected $limit = '';

    /**
     * путь к файлу где лежит explain.xls
     */
    protected $explainPath;

    /**
     * Инициализация
     * @return void
     */
    public function __construct(array $config, $msg)
    {
        $this->config = $config;
        $this->msg = $msg;

        if ($this->explain = $this->config['mode']==='debug') {
            $this->explainPath = $this->config['explain_path'] ?? '../runtime/explain.xls';
            // удаляем файл
            if (file_exists($this->explainPath)) {
                unlink($this->explainPath);
            }
        }
    }

    /**
     * Подключаем ДБ
     * Lazy loading
     * 
     * return void;
     */
    protected function connect()
    {
        if (!is_null($this->connection)) {
            return;
        }
        try {
            $this->connection = new \PDO(
                $this->getDsn(),
                $this->config['user'],
                $this->config['password']
            );
            $this->connection->exec("SET NAMES {$this->config['charset']}");
        } catch (\PDOException $e) {
            throw new DataBaseException($this->msg->i18n('conn_err') . "\n{$e->getMessage()}");
        }
    }

    /**
     * @return \PDO
     */
    abstract protected function getDsn(): string;

    abstract public function isTableExists(string $tableName): boolean;

    public function select(string $tblName, array $where=array(), array $fields=array()): array
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $conditions = $this->prepareConditions($where, 'where');
        $fields = array_merge($fields, $this->fields);
        $fields = $this->prepareFields($fields);

        $query =
                  "SELECT $fields FROM $tblName {$this->join} {$conditions['clause']}"
                . $this->groupByString()
                . $this->orderByString()
                . $this->limit;

        // очищаем переменные
        $this->clearOrderBy();
        $this->clearWhere();
        $this->clearFields();
        $this->clearJoin();
        $this->clearGroupBy();
        $this->clearLimit();

        if ($this->explain) {
            $this->explain($query, $conditions);
        }
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($conditions['vals']) ? $this->prepareRows($stmt->fetchAll()) : array();
    }

    /**
     * Вставляет записи со значениями $fields по условию $where
     */
    public function insert(string $tblName, array $fieldValues=array()): boolean
    {
        $this->connect();

        $fieldValues = array_merge($fieldValues, $this->fields);
        $conditions = $this->prepareConditions($fieldValues, 'insert');
        $placeholder = $this->getPlaceholder($fieldValues);
        $query = "INSERT INTO `$tblName` ({$conditions['clause']}) VALUES ($placeholder)";
        $stmt = $this->connection->prepare($query);
        $this->clearFields();

        if ($this->execute($stmt, $conditions['vals'])) {
            return $this->connection->lastInsertId();
        }
        return false;
    }

    /**
     * Обновляет поля $fieldValues записей по условию $where
     */
    public function update(string $tblName, array $fieldValues=array(), array $where=array())
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $fieldValues = array_merge($fieldValues, $this->fields);
        $updateConditions = $this->prepareConditions($fieldValues, 'update');
        $whereConditions = $this->prepareConditions($where, 'where');
        $query = "UPDATE `$tblName` {$updateConditions['clause']} {$whereConditions['clause']}";
        $stmt = $this->connection->prepare($query);
        $this->clearWhere();
        $this->clearFields();

        return $this->execute($stmt, array_merge($updateConditions['vals'], $whereConditions['vals']));
    }

    /**
     * удаляет записи по условию $where
     */
    public function delete(string $tblName, array $where=array())
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $whereConditions = $this->prepareConditions($where, 'where');
        $stmt = $this->connection->prepare("DELETE FROM `$tblName` {$whereConditions['clause']}");
        $this->clearWhere();
        
        return $this->execute($stmt, $whereConditions['vals']);
    }

    /**
     * быстро очищает таблицу
     */
    public function truncate(string $tblName)
    {
        $this->connect();

        $stmt = $this->connection->prepare("TRUNCATE `$tblName`");
        return $this->execute($stmt);
    }

    public function beginTransaction()
    {
        $this->connect();
        $this->connection->beginTransaction();
    }

    public function endTransaction()
    {
        $this->connection->commit();
    }

	public function selectOne($tblName, $where=array(), $fields=array())
	{
		$rows = $this->select($tblName, $where, $fields);
		return $this->getOneRow($rows);
	}
		
	public function selectById($tblName, $id, $fields=array())
	{
		$rows = $this->select($tblName, compact('id'), $fields);
		return $this->getOneRow($rows);
	}

	public function selectValById($tblName, $field, $id)
	{
		if ($rows = $this->select($tblName, compact('id'), $field)) {
            return $rows[0][$field];
        }
	}
    
    public function query($query)
    {
        $this->connect();

        $stmt = $this->connection->prepare($query);
        if (!$this->execute($stmt)) {
            return false;
        }
        return $stmt;
    }
    
    public function queryAll($query)
    {
        $this->connect();

        $stmt = $this->connection->prepare($query);
        if ($stmt->execute()) {
            return $this->prepareRows($stmt->fetchAll());
        }
        return array();
    }
    
    public function fetchRows($stmt)
    {
        return $this->prepareRows($stmt->fetchAll());
    }
    
    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    
    /**
     * Добавляет условие
     */
    public function addWhere($where)
    {
        $this->where = array_merge($this->where, $where);
    }

    /**
     * устанавливает условие
     */
    public function setWhere($where)
    {
        $this->where = $where;
    }
    
    /**
     * возвращает условие
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * устанавливает поля для выборки
     */
    public function setFields($fieldNames)
    {
        $this->fields = $fieldNames;
    }

    /**
     * возвращает поля для выборки
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    public function setJoin($tblName, $onCond, $joinMode='INNER')
    {
        $this->join .= " $joinMode JOIN $tblName ON $onCond ";
    }

    /**
     * добавляет в массив orderBy новый эт-т
     */
    public function orderBy($fieldName, $order = 'ASC')
    {
        $this->orderBy[$fieldName] = $order;
    }

    /**
     * устанавливает orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    /**
     * возвращает orderBy
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Строка order by cast
     */
    abstract public function orderByCast(string $colName): string;

    protected function orderByString()
    {
        if (count($this->orderBy)===0) {
            return '';
        }
        $orderBy = array();
        foreach ($this->orderBy as $fieldName => $order) {
            $orderBy[] = "$fieldName $order";
        }
        return ' ORDER BY ' . implode(',', $orderBy);
    }

    public function setLimit($limit, $offset = null)
    {
        $this->limit = $limit;
        
        if (!is_null($offset))
            $this->limit = " $offset, {$this->limit}";
            
        $this->limit = " LIMIT {$this->limit} ";
    }
    
    public function getLimit()
    {
        return $this->limit;
    }

    public function setGroupBy($fieldName)
    {
        $this->groupBy = $fieldName;
    }

    public function getGroupBy()
    {
        return $this->groupBy;
    }

    protected function groupByString()
    {
        if ($this->groupBy!=='')
            return " GROUP BY {$this->groupBy} ";
        
        return '';
    }

    /**
     * очищает orderBy
     */
    protected function clearOrderBy()
    {
        $this->orderBy = array();
    }

    /**
     * очищает условие
     */
    protected function clearWhere()
    {
        $this->where = array();
    }
    
    /**
     * очищает поля выборки
     */
    protected function clearFields()
    {
        $this->fields = array();
    }    

    /**
     * очищает join
     */
    protected function clearJoin()
    {
        $this->join = '';
    }
    
    /**
     * очищает limit
     */
    protected function clearLimit()
    {
        $this->limit = '';
    }
    
    /**
     * очищает GroupBy
     */
    protected function clearGroupBy()
    {
        $this->groupBy = '';
    }

	protected function prepareConditions($conditions, $type, $operator='=')
	{
		switch ($type) {
		    case 'where':
			    return $this->createConditions($conditions, 'WHERE', "$operator ?", 'AND');
            case 'update':
                return $this->createConditions($conditions, 'SET', "$operator ?", ',');
		    // TODO: перенести
            case 'insert':
			    return $this->createConditions($conditions, '', '', ',');
		    default:
			    return null;
		}
	}

	protected function createConditions($conditions, $keyword, $operator, $glue)
	{
		$clause = '';
        $vals = array();
        if (count($conditions)!==0) {
            $clauseArr = array();
			foreach ($conditions as $field=> $val) {
                if (preg_match('/ IN/', $field, $matches)!==0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = '(' . implode(',', $val) . ')';
                } elseif (preg_match('/ LIKE/', $field, $matches)!==0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches)!==0)
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . ' ?';
                else
				    $clauseArr[] = $this->prepareField($field) . $operator;

				$vals[] = $val;
			}
			$clause = "$keyword " . implode(" $glue ", $clauseArr);
		}
		return compact('clause', 'vals');
	}

    protected function clearifyField($field, $text)
    {
        $field = str_replace($text, '', $field);
        $field = trim($field);
        $field = $this->prepareField($field);
        return $field;
    }

    protected function prepareFields(array $fields)
    {
        if (count($fields)==0) {
            return '*';
        }
        foreach ($fields as &$field) {
            $field = $this->prepareField($field);
        }
        return implode(',', $fields);
    }

	protected function prepareField($field)
	{
        if (preg_match('/[.( ]/', $field)===0) {
			$field = "`" . trim($field) . "`";
        }
		return $field;
	}

	protected function getPlaceholder($fields)
	{
		$plholdArr = array_fill(0, count($fields), '?');

		return implode(',', $plholdArr);
	}	
	
	protected function getOneRow($rows)
	{
		if (count($rows)>0) {
			$rows =  $this->prepareRows($rows);
			return $rows[0];
		}
	}
    
    protected function prepareRows($rows=array())
    {
        foreach ($rows as &$row) {
            foreach ($row as $key => $value) {
                if (is_integer($key)) {
                    unset($row[$key]);
                }
            }
        }
        return $rows;
    }
    
    protected function execute($stmt, $fields=null)
    {
        if (!$stmt->execute($fields)) {
            //if ('00000' == $this->connection->errorCode())
                //return false;

            throw new DataBaseException($this->msg->i18n('db_err') . ': ' . serialize(self::$_conn->errorInfo()));
        }
        return true;
    }
}
