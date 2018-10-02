<?php
namespace tachyon\db\models;

/**
 * Базовый класс для всех моделей
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Model extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Validator;
    use \tachyon\dic\Lang;

    /**
     * массив [поле => значение]
     * для соотв. аттрибутов модели
     */
    protected $attributes = array();
    /**
     * список типов полей формы
     * для соотв. аттрибутов модели
     * 
     * @return array
     */
    protected static $attributeTypes = array();
    /**
     * список названий аттрибутов модели
     * 
     * @return array
     */
    protected static $attributeNames = array();
    
    /**
     * сценарий валидации
     */
    protected $scenario = '';
    /**
     * ошибки валидации
     */
    protected $errors = array();

    public function __get($var)
    {
        // в случае, если есть такое поле и его значение задано
        return $this->attributes[$var] ?? null;
    }

    public function __set($var, $val)
    {
        $method = 'set' . ucfirst($var);
        if (method_exists($this, $method))
            return $this->$method($val);

        if (!isset($this->$var))
            $this->attributes[$var] = $val;
    }

    /**
     * Присваивание значения аттрибуту модели
     * 
     * @param $name string 
     * @param $value string 
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Присваивание значений аттрибутам модели
     * 
     * @param $arr array 
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if (array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * Возвращает аттрибуты модели
     * 
     * @returns array 
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Возвращает название аттрибута модели
     * 
     * @param $key string 
     * @return array
     */
    public function getAttributeName($key)
    {
        if (!$attributeNames = static::$attributeNames) {
            $attributeNames = array_combine(static::$fields, static::$fields);
        }
        if (array_key_exists($key, $attributeNames)) {
            $attributeName = $attributeNames[$key];
            if (is_array($attributeName))
                return $attributeName[$this->lang->getLanguage()];

            return $attributeName;
        } 
        return ucfirst($key);
    }

    /**
     * Возвращает типы аттрибутов модели
     * 
     * @return array
     */
    public static function getAttributeTypes()
    {
        return static::$attributeTypes;
    }

    /**
     * возвращает тип аттрибута модели
     * 
     * @param $key string
     * @return string
     */
    public function getAttributeType($key)
    {
        if (array_key_exists($key, static::$attributeTypes))
            return static::$attributeTypes[$key];
    }

    /************
    *           *
    * ВАЛИДАЦИЯ *
    *           *
    ************/

    /**
     * attachAttributes
     * присваивание аттрибутов модели с учетом правил валидации
     * 
     * @param $arr array
     * @param $useModelName boolean
     */
    public function attachAttributes($arr, $useModelName=false)
    {
        $modelName = $this->getClassName();
        if ($useModelName) {
            if (!isset($arr[$modelName]))
                return;
                
            $arr = $arr[$modelName];
        }
        foreach ($arr as $key => $value) {
            if (strpos($key, $modelName)!==false)
                $key = str_replace(array($modelName, '[', ']'), '', $key);

            if (array_key_exists($key, $this->rules()))
                $this->attributes[$key] = $value;
        }
    }

    /**
     * возвращает список правил валидации
     * 
     * @return array
     */
    public function rules(): array
    {
        return array();
    }

    /**
     * Валидация полей модели
     * 
     * @param $attrs array массив полей
     * @return boolean
     */
    public function validate(array $attrs=null)
    {
        $this->validator->validate($this, $attrs);
        return empty($this->errors);
    }

    public function getRules($fieldName)
    {
        $rulesRet = array();
        $rules = $this->rules();
        foreach ($rules as $key => $rule) {
            $fieldNames = array_map('trim', explode(',', $key));
            if (in_array($fieldName, $fieldNames))
                $rulesRet = array_merge($rulesRet, $rule);
        }
        return $rulesRet;
    }

    public function isRequired($fieldName)
    {
        $rules = $this->rules();
        foreach ($rules as $key => $rule) {
            $fieldNames = array_map('trim', explode(',', $key));
            if (in_array($fieldName, $fieldNames) && in_array('required', $rule))
                return true;
        }
        return false;
    }

    /**
     * Извлекает ошибку
     * 
     * @param $attr string
     * @return string
     */
    public function getError($attr)
    {
        if (!empty($this->errors) && !empty($this->errors[$attr])) {
            return implode(' ', $this->errors[$attr]);
        }
    }

    /**
     * добавляет ошибку к списку ошибок
     * 
     * @param string $attr
     * @param string $err
     * @return void
     */
    public function addError($attr, $err)
    {
        if (!isset($this->errors[$attr])) {
            $this->errors[$attr] = [];
        }
        $this->errors[$attr][] = $err;
    }
    
    /**
     * устанавливает список ошибок
     * 
     * @param $errors array
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }    

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Вывод всех сообщений об ошибках
     * @return string
     */
    public function getErrorsSummary()
    {
        $retArr = array();
        foreach ($this->errors as $key => $val) {
            $retArr[] = $this->getAttributeName($key) . ': ' . implode(' ', $val);
        }
        return implode('; ', $retArr);
    }

    /**
     * возвращает поля для JS валидации
     * 
     * @return string
     */
    public function getValidationFieldsJs($fields=null)
    {
        $validationItems = array();
        $modelName = $this->getClassName();
        $rules = $this->rules();
        foreach ($rules as $fieldName=> $fieldRules) {
            if (isset($fieldRules['on']) && $fieldRules['on']!==$this->scenario)
                continue;
            if (!is_null($fields) && !in_array($fieldName, $fields))
                continue;
            $attrName = $this->getAttributeName($fieldName);
            $attrType = $this->getAttributeType($fieldName);
            foreach ($fieldRules as $key => $rule) {
                if (is_array($rule)) {
                    if (isset($rule['on']) && $rule['on']!==$this->scenario)
                        continue;
                    foreach ($rule as $subKey=> $subRule)
                        if (is_numeric($key))
                            $check[] = "'$subRule'";
                }
                if (is_numeric($key))
                    $check[] = "'$rule'";
            }
            $validationItems[] = "{
                'type' :'$attrType',
                'title':'$attrName',
                'name' :'{$modelName}[{$fieldName}]',
                'check':[" . implode(',', $check) . "]
            }";
        }
        return '[' . implode(',', $validationItems) . ']';
    }

    /**
     * устанавливает сценарий валидации
     * 
     * @param $scenario string
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
        return $this;
    }
}
