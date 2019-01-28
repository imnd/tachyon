<?php
namespace tachyon\components;

use tachyon\exceptions\ValidationException;

/**
 * class Validator
 * Класс содержащий правила валидации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Validator extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Message;
    use \tachyon\dic\Csrf;

    private $_errors = array();

    public function required($model, $fieldName)
    {
        if ($model->$fieldName==='')
            $this->_addError($fieldName, $this->msg->i18n('fieldRequired'));
    }
    
    public function integer($model, $fieldName)
    {
        $val = $model->$fieldName;
        if (!empty($val) && preg_match('/[^0-9]+/', $val)>0)
            $this->_addError($fieldName, $this->msg->i18n('alpha'));
    }

    public function numerical($model, $fieldName)
    {
        $val = $model->$fieldName;
        if (!empty($val) && preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $val)===0)
            $this->_addError($fieldName, $this->msg->i18n('alpha'));
    }

    public function alpha($model, $fieldName)
    {
        $val = $model->$fieldName;
        if (!empty($val) && preg_match('/[^А-ЯЁа-яёA-Za-z ]+/u', $val)>0)
            $this->_addError($fieldName, $this->msg->i18n('alpha'));
    }

    public function alphaExt($model, $fieldName)
    {
        $val = $model->$fieldName;
        if (!empty($val) && preg_match('/[^А-ЯЁа-яёA-Za-z-_.,0-9 ]+/u', $val)>0)
            $this->_addError($fieldName, $this->msg->i18n('alpha'));
    }

    public function phone($model, $fieldName)
    {
        if (!preg_match('/^\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/', $model->$fieldName))
            $this->_addError($fieldName, $this->msg->i18n('phone'));
    }

    public function password($model, $fieldName)
    {
        $val = $model->$fieldName;
        if (!empty($val) && preg_match('/[^A-Za-z0-9]+/u', $val)>0)
            $this->_addError($fieldName, $this->msg->i18n('password'));
    }

    public function email($model, $fieldName)
    {
        if (!preg_match('/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $model->$fieldName))
            $this->_addError($fieldName, $this->msg->i18n('email'));
    }

    public function equals($model, $fieldName1 = null, $fieldName2 = null)
    {
        $val1 = $model->$fieldName1;
        $val2 = $model->$fieldName2;
        if (!empty($val1) && !empty($val2) && $val1!==$val2)
            $this->_addError($fieldName1, $this->msg->i18n('equals'));
    }

    public function unique($model, $fieldName)
    {
        $fieldVal = $model->$fieldName;
        if (empty($fieldVal))
            return;

        if ($rows = $model->findAllScalar(array($fieldName => $fieldVal)))
            $this->_addError($fieldName, $this->msg->i18n('unique'));
    }    

    public function safe()
    {
        return true;
    }

    public function csrfValidate()
    {
        if ($this->config->getOption('csrf_check')===false)
            return false;
        
        $result = $this->csrf->isTokenValid();
        if (!$result)
            $this->_addError('csrf', 'Неверный csrf token');
            
        return $result;
    }

    /**
     * _addError
     * 
     * @param $fieldName string
     * @param $message string
     */
    private function _addError($fieldName, $message)
    {
        if (!empty($this->_errors[$fieldName]))
            $this->_errors[$fieldName][] = $message;
        else
            $this->_errors[$fieldName] = array($message);
    }

    /**
     * функция валидации полей модели
     * TODO: убрать копипаст
     * 
     * @param \tachyon\db\activeRecord\Model $model
     * @param $attrs array массив полей
     * @return boolean
     * @throws ValidationException
     */
    public function validate(&$model, array $attrs = null)
    {
        $this->csrfValidate();
        $validNotExist = 'Валидатора с таким именем нет.';

        // перебираем все поля
        $attrsArray = $model->getAttributes();
        if (!is_null($attrs)) {
            foreach ($attrs as $attrName)
                unset($attrsArray[$attrName]);
        }
        foreach ($attrsArray as $fieldName => $fieldValue) {
            // если существует правило валидации для данного поля
            if ($fieldRules = $model->getRules($fieldName)) {
                if (isset($fieldRules['on'])) {
                    // если правило не применимо к сценарию
                    if ($fieldRules['on'] !== $model->scenario) {
                        continue;
                    }
                    // убираем, чтобы не мешалось дальше
                    unset($fieldRules['on']);
                }
                foreach ($fieldRules as $key => $rule) {
                    if (is_array($rule)) {
                        if (isset($rule['on'])) {
                            // если правило не применимо к сценарию
                            if ($rule['on'] !== $model->scenario) {
                                continue;
                            }
                            // убираем, чтобы не мешалось дальше
                            unset($rule['on']);
                        }
                        foreach ($rule as $subKey=> $subRule) {
                            if ($subRule=='equals') {
                                $this->equals($model, $fieldName, $rule['with']);
                                continue;
                            }
                            if (!is_numeric($key)) {
                                continue;
                            }
                            if (!method_exists($this, $subRule)) {
                                throw new ValidationException($validNotExist);
                            }
                            $this->$subRule($model, $fieldName);
                        }
                        continue;
                    }
                    if ($rule == 'equals') {
                        $this->equals($model, $fieldName, $fieldRules['with']);
                        continue;
                    }
                    if (!is_numeric($key)) {
                        continue;
                    }
                    if (!method_exists($this, $rule)) {
                        throw new ValidationException($validNotExist);
                    }
                    $this->$rule($model, $fieldName);
                }
            }
        }
        $model->setErrors($this->_errors);
    }
}
