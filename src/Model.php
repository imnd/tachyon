<?php

namespace tachyon;

use tachyon\components\validation\{
    ValidationInterface,
    Validator,
    Validation
};
use tachyon\components\Lang;
use tachyon\traits\{
    ClassName,
    HasAttributes
};

/**
 * Базовый класс для всех моделей
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Model implements ValidationInterface
{
    use ClassName, Validation, HasAttributes;

    /**
     * @var Lang $lang
     */
    protected Lang $lang;
    /**
     * @var Validator $validator
     */
    protected Validator $validator;

    /**
     * @param Lang      $lang
     * @param Validator $validator
     */
    public function __construct(Lang $lang, Validator $validator)
    {
        $this->lang      = $lang;
        $this->validator = $validator;
    }
}
