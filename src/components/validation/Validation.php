<?php

namespace tachyon\components\validation;

use tachyon\exceptions\ValidationException;

/**
 * Трейт валидации
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait Validation
{
    /**
     * сценарий валидации
     *
     * @var string
     */
    protected string $scenario = '';
    /**
     * ошибки валидации
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Присваивание аттрибутов модели с учетом правил валидации
     *
     * @param array   $arr
     * @param boolean $useModelName
     */
    public function attachAttributes(array $arr, bool $useModelName = false): void
    {
        $modelName = $this->getClassName();
        if ($useModelName) {
            if (!isset($arr[$modelName])) {
                return;
            }
            $arr = $arr[$modelName];
        }
        foreach ($arr as $key => $value) {
            if (strpos($key, $modelName) !== false) {
                $key = str_replace([$modelName, '[', ']'], '', $key);
            }
            if (array_key_exists($key, $this->rules())) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Возвращает список правил валидации
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Валидация полей модели
     *
     * @param array $attributes массив полей
     *
     * @return boolean
     * @throws ValidationException
     */
    public function validate(array $attributes = null): bool
    {
        $errors = $this->validator->validate($this, $attributes);
        return empty($errors);
    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    public function getRules(string $fieldName): array
    {
        return $this->validator->getRules($this, $fieldName);
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function isRequired(string $fieldName): bool
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
     * Ошибки
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }

    /**
     * Извлекает ошибку
     *
     * @param string $attr
     *
     * @return null|string
     */
    public function getError(string $attr): ?string
    {
        if ($errors = $this->validator->getErrors()) {
            return implode(' ', $errors[$attr] ?? []);
        }
    }

    /**
     * добавляет ошибку к списку ошибок
     *
     * @param string $fieldName
     * @param string $message
     *
     * @return void
     */
    public function addError(string $fieldName, string $message): void
    {
        $this->validator->addError($fieldName, $message);
    }

    /**
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return !empty($this->validator->getErrors());
    }

    /**
     * Вывод всех сообщений об ошибках
     *
     * @return string
     */
    public function getErrorsSummary(): string
    {
        $retArr = [];
        foreach ($this->validator->getErrors() as $key => $val) {
            $retArr[] = $this->getAttributeName($key) . ': ' . implode(' ', $val);
        }
        return implode('; ', $retArr);
    }

    /**
     * Возвращает поля для JS валидации
     *
     * @param array $fields
     *
     * @return string
     */
    public function getValidationFieldsJs(array $fields = []): string
    {
        $validationItems = [];
        $modelName = $this->getClassName();
        $rules = $this->rules();
        $check = [];
        foreach ($rules as $fieldName => $fieldRules) {
            if (isset($fieldRules['on']) && $fieldRules['on'] !== $this->scenario) {
                continue;
            }
            if (!empty($fields) && !in_array($fieldName, $fields)) {
                continue;
            }
            $attrName = $this->getAttributeName($fieldName);
            $attrType = $this->getAttributeType($fieldName);
            foreach ($fieldRules as $key => $rule) {
                if (is_array($rule)) {
                    if (isset($rule['on']) && $rule['on'] !== $this->scenario) {
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
                'type'  : '$attrType',
                'title' : '$attrName',
                'name'  : '{$modelName}[{$fieldName}]',
                'check' : [" . implode(',', $check) . "]
            }";
        }
        return '[' . implode(',', $validationItems) . ']';
    }

    /**
     * устанавливает сценарий валидации
     *
     * @param string $scenario
     *
     * @return mixed
     */
    public function setScenario(string $scenario)
    {
        $this->scenario = $scenario;
        return $this;
    }
}
