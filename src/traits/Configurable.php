<?php
namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait Configurable
{
    /**
     * setting of objects variables
     */
    public function setParameters(array $params = []): void
    {
        foreach ($params as $param => $value) {
            $this->$param = $value;
        }
    }
}
