<?php
namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait HasProperties
{
    /**
     * setting of objects properties
     */
    public function setProperties(array $properties = []): static
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        return $this;
    }
}
