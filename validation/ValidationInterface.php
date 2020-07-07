<?php

namespace tachyon\validation;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
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
     * @param $attrs array массив полей
     *
     * @return boolean
     */
    public function validate(array $attributes = null);

    /**
     * Возвращает список правил валидации для поля $fieldName
     *
     * @return array
     */
    public function getRules(string $fieldName);

    /**
     * Добавление ошибки в массив ошибок
     *
     * @return array
     */
    public function addError(string $fieldName, string $message);

    /**
     * ошибки
     *
     * @return array
     */
    public function getErrors();

    /**
     * Сообщение об ошибках
     *
     * @return array
     */
    public function getErrorsSummary();
}
