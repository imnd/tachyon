<?php

namespace tachyon\validation;

use tachyon\{
    exceptions\ValidationException,
    components\Message
};

/**
 * Класс содержащий правила валидации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Validator
{
    /**
     * @var Message
     */
    protected Message $msg;
    private array $_errors = [];

    public function __construct(Message $msg)
    {
        $this->msg = $msg;
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function required($model, $fieldName): void
    {
        if ($model->getAttribute($fieldName) === '') {
            $this->addError($fieldName, $this->msg->i18n('fieldRequired'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function integer($model, $fieldName): void
    {
        $val = $model->getAttribute($fieldName);
        if (!empty($val) && preg_match('/[^0-9]+/', $val) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function numerical($model, $fieldName): void
    {
        $val = $model->getAttribute($fieldName);
        if (!empty($val) && preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $val) === 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function alpha($model, $fieldName): void
    {
        $val = $model->getAttribute($fieldName);
        if (!empty($val) && preg_match('/[^А-ЯЁа-яёA-Za-z ]+/u', $val) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function alphaExt($model, $fieldName): void
    {
        $val = $model->getAttribute($fieldName);
        if (!empty($val) && preg_match('/[^А-ЯЁа-яёA-Za-z-_.,0-9 ]+/u', $val) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function phone($model, $fieldName): void
    {
        if (!preg_match('/^\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/', $model->getAttribute($fieldName))) {
            $this->addError($fieldName, $this->msg->i18n('phone'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function password($model, $fieldName): void
    {
        $val = $model->getAttribute($fieldName);
        if (!empty($val) && preg_match('/[^A-Za-z0-9]+/u', $val) > 0) {
            $this->addError($fieldName, $this->msg->i18n('password'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function email($model, $fieldName): void
    {
        if (!preg_match(
            '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/',
            $model->getAttribute($fieldName)
        )) {
            $this->addError($fieldName, $this->msg->i18n('email'));
        }
    }

    /**
     * @param      $model
     * @param null $fieldName1
     * @param null $fieldName2
     */
    public function equals($model, $fieldName1 = null, $fieldName2 = null): void
    {
        $val1 = $model->getAttribute($fieldName1);
        $val2 = $model->getAttribute($fieldName2);
        if (!empty($val1) && !empty($val2) && $val1 !== $val2) {
            $this->addError($fieldName1, $this->msg->i18n('equals'));
        }
    }

    /**
     * @param $model
     * @param $fieldName
     */
    public function unique($model, $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if ($rows = $model->findAllRaw([$fieldName => $fieldVal])) {
            $this->addError($fieldName, $this->msg->i18n('unique'));
        }
    }

    /**
     * @return bool
     */
    public function safe(): bool
    {
        return true;
    }

    /**
     * @param string $fieldName
     * @param string $message
     *
     * @return void
     */
    public function addError(string $fieldName, string $message): void
    {
        if (empty($this->_errors[$fieldName])) {
            $this->_errors[$fieldName] = [];
        }
        $this->_errors[$fieldName][] = $message;
    }

    /**
     * @param $object
     * @param $fieldName
     *
     * @return array
     */
    public function getRules($object, $fieldName): array
    {
        $rulesArray = [];
        $rules = $object->rules();
        foreach ($rules as $key => $rule) {
            $fieldNames = array_map('trim', explode(',', $key));
            if (in_array($fieldName, $fieldNames)) {
                if (!is_array($rule)) {
                    $rule = [$rule];
                }
                $rulesArray = array_merge($rulesArray, $rule);
            }
        }
        return $rulesArray;
    }

    /**
     * Валидация полей модели/сущности
     * 
     * @param mixed $object
     * @param array $attrs массив полей
     *
     * @return array
     * 
     * @throws ValidationException
     */
    public function validate($object, array $attrs = null): array
    {
        // перебираем все поля
        $attrsArray = $object->getAttributes();
        if (!is_null($attrs)) {
            $attrsArray = array_intersect_key($attrsArray, array_flip($attrs));
        }
        $methodNotExist = 'Валидатора с таким именем нет.';
        foreach ($attrsArray as $fieldName => $fieldValue) {
            // если существует правило валидации для данного поля
            if ($fieldRules = $this->getRules($object, $fieldName)) {
                if (!$this->_on($fieldRules, $object)) {
                    continue;
                }
                foreach ($fieldRules as $key => $rule) {
                    if (is_array($rule)) {
                        if (!$this->_on($rule, $object)) {
                            continue;
                        }
                        foreach ($rule as $subKey => $subRule) {
                            if ($subRule === 'equals') {
                                $this->equals($object, $fieldName, $rule['with']);
                                continue;
                            }
                            if (!is_numeric($key)) {
                                continue;
                            }
                            if (!method_exists($this, $subRule)) {
                                throw new ValidationException($methodNotExist);
                            }
                            $this->$subRule($object, $fieldName);
                        }
                        continue;
                    }
                    if ($rule === 'equals') {
                        $this->equals($object, $fieldName, $fieldRules['with']);
                        continue;
                    }
                    if (!is_numeric($key)) {
                        continue;
                    }
                    if (!method_exists($this, $rule)) {
                        throw new ValidationException($methodNotExist);
                    }
                    $this->$rule($object, $fieldName);
                }
            }
        }
        return $this->_errors;
    }

    /**
     * @param array $rule
     * @param       $object
     *
     * @return boolean
     */
    private function _on(&$rule, $object): bool
    {
        if (isset($rule['on'])) {
            // если правило не применимо к сценарию
            if ($rule['on'] !== $object->scenario) {
                return false;
            }
            // убираем, чтобы не мешалось дальше
            unset($rule['on']);
        }
        return true;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Сообщение об ошибках
     *
     * @param $object
     *
     * @return string
     */
    public function getErrorsSummary($object): string
    {
        $summary = '';
        foreach ($this->_errors as $attribute => $errors) {
            $summary .= "{$object->getCaption($attribute)}: " . implode(', ', $errors) . "\n";
        }
        return $summary;
    }
}
