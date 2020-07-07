<?php

namespace tachyon\validation;

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
     */
    protected $scenario = '';
    /**
     * ошибки валидации
     */
    protected $errors = [];

    /**
     * Присваивание аттрибутов модели с учетом правил валидации
     *
     * @param array $arr
     * @param boolean $useModelName
     */
    public function attachAttributes(array $arr, bool $useModelName = false)
    {
        $modelName = $this->getClassName();
        if ($useModelName) {
            if (!isset($arr[$modelName])) {
                return null;
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
     * возвращает список правил валидации
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
     * @param $attrs array массив полей
     *
     * @return boolean
     * @throws \tachyon\exceptions\ValidationException
     */
    public function validate(array $attrs = null): bool
    {
        $errors = $this->validator->validate($this, $attrs);
        return empty($errors);
    }

    public function getRules(string $fieldName)
    {
        return $this->validator->getRules($this, $fieldName);
    }

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
     * ошибки
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
    public function getError(string $attr)
    {
        if ($errors = $this->validator->getErrors()) {
            return implode(' ', $errors[$attr] ?? []);
        }
    }

    /**
     * добавляет ошибку к списку ошибок
     *
     * @param string $attr
     * @param string $message
     *
     * @return void
     */
    public function addError(string $attr, string $message): void
    {
        $this->validator->addError($attr, $message);
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
