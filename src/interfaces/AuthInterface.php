<?php

namespace tachyon\interfaces;

use tachyon\exceptions\HttpException;

/**
 * @author imndsu@gmail.com
 */
interface AuthInterface
{
    /**
     * redirects user to login
     */
    public function accessDenied(): void;

    /**
     * user not authorized
     *
     * @throws HttpException
     */
    public function unauthorised($msg): void;

    public function isAuthorised(): bool;

    public function checkAccess(): ?bool;
}
