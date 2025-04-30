<?php

namespace tachyon\components\validation;

/**
 * @author imndsu@gmail.com
 */
interface ValidationInterface
{
    /**
     * Возвращает список правил валидации
     *
     * @return array
     */
    public function rules(): array;

    /**
     * Валидация полей сущности
     *
     * @param array $attributes массив полей
     *
     * @return boolean
     */
    public function validate(array $attributes = null): bool;

    /**
     * Возвращает список правил валидации для поля $fieldName
     *
     * @param string $fieldName валидируемое поле
     *
     * @return array
     */
    public function getRules(string $fieldName): array;

    /**
     * Добавление ошибки в массив ошибок
     *
     * @param string $fieldName валидируемое поле
     * @param string $message сообщение об ошибке
     *
     * @return void
     */
    public function addError(string $fieldName, string $message): void;

    /**
     * Ошибки
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Сообщение об ошибках
     *
     * @return string
     */
    public function getErrorsSummary(): string;
}
