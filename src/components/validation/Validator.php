<?php

namespace tachyon\components\validation;

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
    private Message $msg;
    /**
     * @var array
     */
    private array $errors = [];

    public function __construct(Message $msg)
    {
        $this->msg = $msg;
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function required($model, string $fieldName): void
    {
        if ($model->getAttribute($fieldName) === '') {
            $this->addError($fieldName, $this->msg->i18n('fieldRequired'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function integer($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!empty($fieldVal) && preg_match('/[^0-9]+/', $fieldVal) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function numerical($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!empty($fieldVal) && preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $fieldVal) === 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function alpha($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!empty($fieldVal) && preg_match('/[^А-ЯЁа-яёA-Za-z ]+/u', $fieldVal) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function alphaExt($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!empty($fieldVal) && preg_match('/[^А-ЯЁа-яёA-Za-z-_.,0-9 ]+/u', $fieldVal) > 0) {
            $this->addError($fieldName, $this->msg->i18n('alpha'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function phone($model, string $fieldName): void
    {
        if (!preg_match('/^\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/', $model->getAttribute($fieldName))) {
            $this->addError($fieldName, $this->msg->i18n('phone'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function password($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!empty($fieldVal) && preg_match('/[^A-Za-z0-9]+/u', $fieldVal) > 0) {
            $this->addError($fieldName, $this->msg->i18n('password'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function email($model, string $fieldName): void
    {
        if (!preg_match(
            '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/',
            $model->getAttribute($fieldName)
        )) {
            $this->addError($fieldName, $this->msg->i18n('email'));
        }
    }

    /**
     * @param Model       $model
     * @param string|null $fieldName1
     * @param string|null $fieldName2
     */
    public function equals(
        Model $model,
        string $fieldName1 = null,
        string $fieldName2 = null
    ): void
    {
        $val1 = $model->getAttribute($fieldName1);
        $val2 = $model->getAttribute($fieldName2);
        if (!empty($val1) && !empty($val2) && $val1 !== $val2) {
            $this->addError($fieldName1, $this->msg->i18n('equals'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function unique($model, string $fieldName): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if ($rows = $model->findAllRaw([$fieldName => $fieldVal])) {
            $this->addError($fieldName, $this->msg->i18n('unique'));
        }
    }

    /**
     * @param mixed $model
     * @param string $fieldName
     */
    public function in($model, string $fieldName, $vals): void
    {
        if (!$fieldVal = $model->getAttribute($fieldName)) {
            return;
        }
        if (!is_array($vals)) {
            $vals = explode(',', $vals);
        }
        if (!in_array($fieldVal, $vals)) {
            $this->addError($fieldName, $this->msg->i18n('in', [
                'list' => implode(', ', $vals),
            ]));
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
        if (empty($this->errors[$fieldName])) {
            $this->errors[$fieldName] = [];
        }
        $this->errors[$fieldName][] = $message;
    }

    /**
     * @param mixed $object
     * @param mixed $fieldName
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
                $rulesArray = array_merge($rulesArray, (array)$rule);
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
     * @throws ValidationException
     */
    public function validate($object, array $attrs = null): array
    {
        // перебираем все поля
        $attrsArray = $object->getAttributes();
        if (!is_null($attrs)) {
            $attrsArray = array_intersect_key($attrsArray, array_flip($attrs));
        }
        $methodNotExist = 'There is no validator: %name.';
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
                                throw new ValidationException($this->msg->i18n($methodNotExist, [
                                    'name' => $subRule,
                                ]));
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
                    if ($colon = strpos($rule, ':')) {
                        $params = substr($rule, $colon + 1);
                        $rule = substr($rule, 0, $colon);
                    }
                    if (!method_exists($this, $rule)) {
                        throw new ValidationException($this->msg->i18n($methodNotExist, [
                            'name' => $rule,
                        ]));
                    }
                    $this->$rule($object, $fieldName, $params ?? null);
                }
            }
        }
        return $this->errors;
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
        return $this->errors;
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
        foreach ($this->errors as $attribute => $errors) {
            $summary .= "{$object->getCaption($attribute)}: " . implode(', ', $errors) . "\n";
        }
        return $summary;
    }
}
