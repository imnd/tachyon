<?php

namespace tachyon\exceptions;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class HttpException extends \Exception
{
    /** @const Коды */
    const OK                    = 200;
    const MOVED_PERMANENTLY     = 301;
    const BAD_REQUEST           = 400;
    const UNAUTHORIZED          = 401;
    const FORBIDDEN             = 403;
    const NOT_FOUND             = 404;
    const INTERNAL_SERVER_ERROR = 500;

    /** @const Коды и их сообщения */
    const HTTP_STATUS_CODES = [
        self::OK                    => 'OK',
        self::MOVED_PERMANENTLY     => 'Moved Permanently',
        self::BAD_REQUEST           => 'Bad Request',
        self::UNAUTHORIZED          => 'Unauthorized',
        self::FORBIDDEN             => 'Forbidden',
        self::NOT_FOUND             => 'Not Found',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
    ];
}
