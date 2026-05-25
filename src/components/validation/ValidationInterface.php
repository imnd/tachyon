<?php

namespace tachyon\components\validation;

/**
 * @author imndsu@gmail.com
 */
interface ValidationInterface
{
    /**
     * Returns list of validation rules
     *
     * @return array
     */
    public function rules(): array;

    /**
     * Validation of entity fields
     *
     * @param array $attributes fields array
     *
     * @return boolean
     */
    public function validate(array $attributes = null): bool;

    /**
     * Returns list of validation rules for field $fieldName
     *
     * @param string $fieldName validated field
     *
     * @return array
     */
    public function getRules(string $fieldName): array;

    /**
     * Adding error to errors array
     *
     * @param string $fieldName validated field
     * @param string $message error message
     *
     * @return void
     */
    public function addError(string $fieldName, string $message): void;

    /**
     * Errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Error message
     *
     * @return string
     */
    public function getErrorsSummary(): string;
}
