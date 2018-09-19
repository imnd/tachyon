<?php
namespace tachyon\db;

/**
 * ДБАЛ (на PDO)
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Db extends \tachyon\Component
{
    # DIC
    use \tachyon\dic\Config;
    use \tachyon\dic\Message;

    /**
     * соединение с БД (PDO)
     */
    private static $_conn;

    /**
     * поля для выборки
     */
    private $_fields = array();
    /**
     * условия для выборки
     */
    private $_where = array();
    private $_join = '';
    private $_groupBy = '';
    private $_orderBy = array();
    private $_limit = '';

    /**
     * путь к файлу где лежит explain.xls
     */
    private static $explainPath;

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        // подключаем ДБ
        $this->_connect();
            
        if ($this->get('config')->getOption('mode')!=='debug')
            return;

        // путь к файлу explain для запросов
        self::$explainPath = '../runtime/explain.xls';
        // удаляем файл
        if (file_exists(self::$explainPath))
            unlink(self::$explainPath);
    }

    /**
     * подключаем ДБ
     * 
     * return void;
     */
    private function _connect()
    {
        if (!is_null(self::$_conn))
            return;

        try {
            $dbOptions = $this->get('config')->getOption('db');
            self::$_conn = new \PDO(
                'mysql:host=' . $dbOptions['host'] .
                ';dbname=' . $dbOptions['name'],
                $dbOptions['user'],
                $dbOptions['password']
            );
            self::$_conn->exec('set names ' . $dbOptions['char_set']);
        } catch (\PDOException $e) {
            throw new \Exception($this->get('msg')->i18n('conn_err'));
        }
    }

    public function beginTransaction()
    {
        self::$_conn->beginTransaction();
    }

    public function endTransaction()
    {
        self::$_conn->commit();
    }

    public function isTableExists($tableName)
    {
        $stmt = self::$_conn->prepare("SHOW TABLES LIKE ?");
        $this->_execute($stmt, array(str_replace('`', '', $tableName)));
        // если такая таблица существует
        if (count($stmt->fetchAll())>0)
            return true;

        return false;
    }
    
	public function select($tblName, $where=array(), $fields=array())
	{
        $where = array_merge($where, $this->_where);
        $conditions = $this->_prepareConditions($where, 'where');
        $fields = array_merge($fields, $this->_fields);
        $fields = $this->_prepareFields($fields);

        $query = "SELECT $fields FROM $tblName {$this->_join} {$conditions['clause']}" . $this->_groupByString() . $this->_orderByString() . $this->_limit;

        // очищаем переменные
        $this->_clearOrderBy();
        $this->_clearWhere();
        $this->_clearFields();
        $this->_clearJoin();
        $this->_clearGroupBy();
        $this->_clearLimit();

        if ($this->get('config')->getOption('mode')==='debug')
            $this->_explain($query, $conditions);

		$stmt = self::$_conn->prepare($query);
        $rows = $stmt->execute($conditions['vals']) ? $this->_prepareRows($stmt->fetchAll()) : array();
		return $rows;
	}

	public function selectOne($tblName, $where=array(), $fields=array())
	{
		$rows = $this->select($tblName, $where, $fields);
		return $this->_getOneRow($rows);
	}
		
	public function selectById($tblName, $id, $fields=array())
	{
		$rows = $this->select($tblName, compact('id'), $fields);
		return $this->_getOneRow($rows);
	}

	public function selectValById($tblName, $field, $id)
	{
		$rows = $this->select($tblName, compact('id'), $field);
		$row = $rows['0'];
		return $row[$field];
	}

	public function insert($tblName, $fields=array(), $check=false)
	{
        $fields = array_merge($fields, $this->_fields);
		$conditions = $this->_prepareConditions($fields, 'insert');
		$placeholder = $this->_getPlaceholder($fields);
        $query = "INSERT INTO `$tblName` ({$conditions['clause']}) VALUES ($placeholder)";
		$stmt = self::$_conn->prepare($query);
        $this->_clearFields();

        if ($this->_execute($stmt, $conditions['vals']))
		    return self::$_conn->lastInsertId();

        return false;
	}

	public function update($tblName, $fields=array(), $where=array(), $check=false)
	{
        $where = array_merge($where, $this->_where);
        $fields = array_merge($fields, $this->_fields);
		$updateConditions = $this->_prepareConditions($fields, 'update');
		$whereConditions = $this->_prepareConditions($where, 'where');
		$query = "UPDATE `$tblName` {$updateConditions['clause']} {$whereConditions['clause']}";
        $stmt = self::$_conn->prepare($query);
        $this->_clearWhere();
        $this->_clearFields();

        return $this->_execute($stmt, array_merge($updateConditions['vals'], $whereConditions['vals']));
	}
	
	public function delete($tblName, $where=array(), $check=false)
	{
        $where = array_merge($where, $this->_where);
		$whereConditions = $this->_prepareConditions($where, 'where');
		$stmt = self::$_conn->prepare("DELETE FROM `$tblName` {$whereConditions['clause']}");
        $this->_clearWhere();
		
		return $this->_execute($stmt, $whereConditions['vals']);
	}

    /**
     * быстро очищает таблицу
     */
    public function truncate($tblName)
    {
        $stmt = self::$_conn->prepare("TRUNCATE `$tblName`");
        return $this->_execute($stmt);
    }
    
    public function query($query)
    {
        $stmt = self::$_conn->prepare($query);
        if (!$this->_execute($stmt))
            return false;
        
        return $stmt;
    }
    
    public function queryAll($query)
    {
        $stmt = self::$_conn->prepare($query);
        if ($stmt->execute())
            return $this->_prepareRows($stmt->fetchAll());

        return array();
    }
    
    public function fetchRows($stmt)
    {
        return $this->_prepareRows($stmt->fetchAll());
    }
    
    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    
    /**
     * Добавляет условие
     */
    public function addWhere($where)
    {
        $this->_where = array_merge($this->_where, $where);
    }

    /**
     * устанавливает условие
     */
    public function setWhere($where)
    {
        $this->_where = $where;
    }
    
    /**
     * возвращает условие
     */
    public function getWhere()
    {
        return $this->_where;
    }

    /**
     * устанавливает поля для выборки
     */
    public function setFields($fieldNames)
    {
        $this->_fields = $fieldNames;
    }

    /**
     * возвращает поля для выборки
     */
    public function getFields()
    {
        return $this->_fields;
    }
    
    public function setJoin($tblName, $onCond, $joinMode='INNER')
    {
        $this->_join .= " $joinMode JOIN $tblName ON $onCond ";
    }

    /**
     * добавляет в массив _orderBy новый эт-т
     */
    public function orderBy($fieldName, $order = 'ASC')
    {
        $this->_orderBy[$fieldName] = $order;
    }

    /**
     * устанавливает _orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->_orderBy = $orderBy;
    }

    /**
     * возвращает _orderBy
     */
    public function getOrderBy()
    {
        return $this->_orderBy;
    }

    private function _orderByString()
    {
        if (count($this->_orderBy)===0)
            return '';
        
        $orderBy = array();
        foreach ($this->_orderBy as $fieldName => $order)
            $orderBy[] = "$fieldName $order";

        return ' ORDER BY ' . implode(',', $orderBy);
    }

    public function setLimit($limit, $offset = null)
    {
        $this->_limit = $limit;
        
        if (!is_null($offset))
            $this->_limit = " $offset, {$this->_limit}";
            
        $this->_limit = " LIMIT {$this->_limit} ";
    }
    
    public function getLimit()
    {
        return $this->_limit;
    }

    public function setGroupBy($fieldName)
    {
        $this->_groupBy = $fieldName;
    }

    public function getGroupBy()
    {
        return $this->_groupBy;
    }

    private function _groupByString()
    {
        if ($this->_groupBy!=='')
            return " GROUP BY {$this->_groupBy} ";
        
        return '';
    }

    /**
     * очищает _orderBy
     */
    private function _clearOrderBy()
    {
        $this->_orderBy = array();
    }

    /**
     * очищает условие
     */
    private function _clearWhere()
    {
        $this->_where = array();
    }
    
    /**
     * очищает поля выборки
     */
    private function _clearFields()
    {
        $this->_fields = array();
    }    

    /**
     * очищает _join
     */
    private function _clearJoin()
    {
        $this->_join = '';
    }
    
    /**
     * очищает _limit
     */
    private function _clearLimit()
    {
        $this->_limit = '';
    }
    
    /**
     * очищает GroupBy
     */
    private function _clearGroupBy()
    {
        $this->_groupBy = '';
    }

	private function _prepareConditions($conditions, $type, $operator='=')
	{
		switch ($type) {
		    case 'where':
			    return $this->_createConditions($conditions, 'WHERE', "$operator ?", 'AND');
            case 'update':
                return $this->_createConditions($conditions, 'SET', "$operator ?", ',');
		    // TODO: перенести
            case 'insert':
			    return $this->_createConditions($conditions, '', '', ',');
		    default:
			    return null;
		}
	}

	private function _createConditions($conditions, $keyword, $operator, $glue)
	{
		$clause = '';
        $vals = array();
        if (count($conditions)!==0) {
            $clauseArr = array();
			foreach ($conditions as $field=> $val) {
                if (preg_match('/ IN/', $field, $matches)!==0) {
                    $clauseArr[] = $this->_clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = '(' . implode(',', $val) . ')';
                } elseif (preg_match('/ LIKE/', $field, $matches)!==0) {
                    $clauseArr[] = $this->_clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches)!==0)
                    $clauseArr[] = $this->_clearifyField($field, $matches[0]) . $matches[0] . ' ?';
                else
				    $clauseArr[] = $this->_prepareField($field) . $operator;

				$vals[] = $val;
			}
			$clause = "$keyword " . implode(" $glue ", $clauseArr);
		}
		return compact('clause', 'vals');
	}

    private function _clearifyField($field, $text)
    {
        $field = str_replace($text, '', $field);
        $field = trim($field);
        $field = $this->_prepareField($field);
        return $field;
    }

    private function _prepareFields(array $fields)
    {
        if (count($fields)==0)
            return '*';
            
        foreach ($fields as &$field)
            $field = $this->_prepareField($field);

        return implode(',', $fields);
    }

	private function _prepareField($field)
	{
        if (preg_match('/[.( ]/', $field)===0)
			$field = "`" . trim($field) . "`";

		return $field;
	}

	private function _getPlaceholder($fields)
	{
		$plholdArr = array_fill(0, count($fields), '?');

		return implode(',', $plholdArr);
	}	
	
	private function _getOneRow($rows)
	{
		if (count($rows)>0) {
			$rows =  $this->_prepareRows($rows);
			return $rows[0];
		}
	}
    
    private function _prepareRows($rows=array())
    {
        foreach ($rows as &$row)
            foreach ($row as $key => $value)
                if (is_integer($key))
                    unset($row[$key]);

        return $rows;
    }
    
    private function _execute($stmt, $fields=null)
    {
        if (!$stmt->execute($fields)) {
            if ('00000' == self::$_conn->errorCode())
                return false;

            throw new \Exception($this->msg->i18n('db_err') . ': ' . serialize(self::$_conn->errorInfo()));
        }
        return true;
    }

    /**
     * выдает отчет EXPLAIN
     */
    private function _explain($query, $conditions1, $conditions2=null)
    {
        $query = trim(preg_replace('!\s+!', ' ', str_replace(array("\r", "\n"), ' ', $query)));
        $output = "query: $query\r\n";
        $output .= "id\tselect_type\ttable\ttype\tpossible_keys\tkey\tkey_len\tref\trows\tExtra\r\n";

        $fields = $conditions1['vals'];
        if (!is_null($conditions2))
            $fields = array_merge($fields, $conditions2['vals']);
        
        // выводим в файл
        $stmt = self::$_conn->prepare("EXPLAIN $query");
        try {
            $this->_execute($stmt, $fields);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                foreach ($row as $key => $value)
                    if (is_numeric($key))
                        $output .= "$value\t";

                $output .= "\r\n";
            }
            $file = fopen(self::$explainPath, "w");
            fwrite($file, $output);
            fclose($file);
        } catch (\Exception $e) {}
    }
}
