<?php

namespace tachyon;

use tachyon\components\validation\{
    ValidationInterface,
    Validator,
    Validation
};
use tachyon\components\Lang;
use tachyon\traits\HasAttributes;

/**
 * Basic class for all models
 *
 * @author imndsu@gmail.com
 */
abstract class Model implements ValidationInterface
{
    use Validation, HasAttributes;

    protected Lang $lang;
    protected Validator $validator;

    public function __construct(Lang $lang, Validator $validator)
    {
        $this->lang      = $lang;
        $this->validator = $validator;
    }
}
