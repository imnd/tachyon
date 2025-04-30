<?php

namespace tachyon\components\validation;

use tachyon\exceptions\ValidationException;
use tachyon\Helpers\ClassHelper;

/**
 * Трейт валидации
 *
 * @author imndsu@gmail.com
 */
trait Validation
{
    /**
     * сценарий валидации
     */
    protected string $scenario = '';
    /**
     * ошибки валидации
     */
    protected array $errors = [];

    /**
     * Присваивание аттрибутов модели с учетом правил валидации
     */
    public function attachAttributes(array $arr, bool $useModelName = false): void
    {
        $modelName = ClassHelper::getClassName($this);
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
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Валидация полей модели
     *
     * @param array | null $attributes массив полей
     *
     * @throws ValidationException
     */
    public function validate(array $attributes = null): bool
    {
        $errors = $this->validator->validate($this, $attributes);
        return empty($errors);
    }

    public function getRules(string $fieldName): array
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
     * Ошибки
     */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }

    /**
     * Извлекает ошибку
     */
    public function getError(string $attr): ?string
    {
        if ($errors = $this->validator->getErrors()) {
            return implode(' ', $errors[$attr] ?? []);
        }

        return null;
    }

    /**
     * добавляет ошибку к списку ошибок
     */
    public function addError(string $fieldName, string $message): void
    {
        $this->validator->addError($fieldName, $message);
    }

    public function hasErrors(): bool
    {
        return !empty($this->validator->getErrors());
    }

    /**
     * Вывод всех сообщений об ошибках
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
     */
    public function getValidationFieldsJs(array $fields = []): string
    {
        $validationItems = [];
        $modelName = ClassHelper::getClassName($this);
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
     */
    public function setScenario(string $scenario): static
    {
        $this->scenario = $scenario;
        return $this;
    }
}
