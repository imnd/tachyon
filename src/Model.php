<?php
namespace tachyon;

use
    tachyon\validation\ValidationInterface,
    tachyon\validation\Validator,
    tachyon\components\Lang,
    tachyon\traits\ClassName
;

/**
 * Базовый класс для всех моделей
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Model implements ValidationInterface
{
    use ClassName;

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
    protected $attributeTypes = array();
    /**
     * список названий аттрибутов модели
     * 
     * @return array
     */
    protected $attributeNames = array();

    /**
     * сценарий валидации
     */
    protected $scenario = '';
    /**
     * ошибки валидации
     */
    protected $errors = array();

    /**
     * @var tachyon\components\Lang $lang
     */
    protected $lang;
    /**
     * @var tachyon\validation\Validator $validator
     */
    protected $validator;

    /**
     * @param Lang $lang
     * @param Validator $validator
     */
    public function __construct(Lang $lang, Validator $validator)
    {
        $this->lang = $lang;
        $this->validator = $validator;
    }

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
     * @param $attribute
     * @param $value string
     * @return Model
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }

    /**
     * Извлечение значения аттрибута $name
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name];
    }

    /**
     * Присваивание значений аттрибутам модели
     *
     * @param $attributes array
     * @return Model
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
        if (array_key_exists($key, $this->attributeNames)) {
            $attributeName = $this->attributeNames[$key];
            if (is_array($attributeName)) {
                return $attributeName[$this->lang->getLanguage()];
            }
            return $attributeName;
        } 
        return ucfirst($key);
    }

    /**
     * Возвращает типы аттрибутов модели
     * 
     * @return array
     */
    public function getAttributeTypes()
    {
        return $this->attributeTypes;
    }

    /**
     * возвращает тип аттрибута модели
     * 
     * @param $key string
     * @return string
     */
    public function getAttributeType($key)
    {
        if (array_key_exists($key, $this->attributeTypes)) {
            return $this->attributeTypes[$key];
        }
    }

    /*************
    *            *
    * VALIDATION *
    *            *
    *************/

    /**
     * Присваивание аттрибутов модели с учетом правил валидации
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
     * @throws exceptions\ValidationException
     */
    public function validate(array $attrs=null)
    {
        $errors = $this->validator->validate($this, $attrs);
        return empty($errors);
    }

    public function getRules($fieldName)
    {
        return $this->validator->getRules($this, $fieldName);
    }

    public function isRequired($fieldName)
    {
        $rules = $this->rules();
        foreach ($rules as $key => $rule) {
            $fieldNames = array_map('trim', explode(',', $key));
            if (in_array($fieldName, $fieldNames) && in_array('required', $rule)) {
                return true;
            }
        }
        return false;
    }

    /**
     * ошибки
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->validator->getErrors();
    }

    /**
     * Извлекает ошибку
     * 
     * @param $attr string
     * @return string
     */
    public function getError($attr)
    {
        $errors = $this->validator->getErrors();
        if (!empty($errors) && !empty($errors[$attr])) {
            return implode(' ', $errors[$attr]);
        }
    }

    /**
     * добавляет ошибку к списку ошибок
     * 
     * @param string $attr
     * @param string $message
     * @return void
     */
    public function addError($attr, $message)
    {
        $this->validator->addError($attr, $message);
    }
    
    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->validator->getErrors());
    }

    /**
     * Вывод всех сообщений об ошибках
     * @return string
     */
    public function getErrorsSummary()
    {
        $retArr = array();
        foreach ($this->validator->getErrors() as $key => $val) {
            $retArr[] = $this->getAttributeName($key) . ': ' . implode(' ', $val);
        }
        return implode('; ', $retArr);
    }

    /**
     * возвращает поля для JS валидации
     *
     * @param null $fields
     * @return string
     */
    public function getValidationFieldsJs($fields=null)
    {
        $validationItems = array();
        $modelName = $this->getClassName();
        $rules = $this->rules();
        foreach ($rules as $fieldName => $fieldRules) {
            if (isset($fieldRules['on']) && $fieldRules['on']!==$this->scenario) {
                continue;
            }
            if (!is_null($fields) && !in_array($fieldName, $fields)) {
                continue;
            }
            $attrName = $this->getAttributeName($fieldName);
            $attrType = $this->getAttributeType($fieldName);
            foreach ($fieldRules as $key => $rule) {
                if (is_array($rule)) {
                    if (isset($rule['on']) && $rule['on']!==$this->scenario) {
                        continue;
                    }
                    foreach ($rule as $subKey => $subRule) {
                        if (is_numeric($key)) {
                            $check[] = "'$subRule'";
                        }
                    }
                }
                if (is_numeric($key)) {
                    $check[] = "'$rule'";
                }
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
     * @return Model
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
        return $this;
    }
}
