<?php

namespace tachyon;

use tachyon\components\validation\{
    ValidationInterface,
    Validator,
    Validation
};
use tachyon\components\Lang;

/**
 * Basic class for all models
 *
 * @author imndsu@gmail.com
 */
abstract class Model implements ValidationInterface
{
    use Validation;

    public function __construct(protected Lang $lang, protected Validator $validator)
    {
    }

    /**
     * array [field => value] for the corresponding attributes of the model
     */
    protected array $attributes = [];
    /**
     * List of form types for the corresponding attributes of the model
     */
    protected array $attributeTypes = [];
    /**
     * List of names of the attributes of the model
     */
    protected array $attributeNames = [];

    public function __get(string $var)
    {
        // If there is such a field and its value is set
        return $this->attributes[$var] ?? null;
    }

    public function __set(string $var, $val)
    {
        $method = 'set' . ucfirst($var);
        if (method_exists($this, $method)) {
            $this->$method($val);
        }
        if (!isset($this->$var)) {
            $this->attributes[$var] = $val;
        }
    }

    public function __isset(string $var)
    {
        return isset($this->attributes[$var]);
    }

    /**
     * Assigning the value to the model attribute
     */
    public function setAttribute(string $attribute, string $value): static
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }

    /**
     * Extracting the attribute $name
     */
    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name];
    }

    /**
     * Assigning the values of the model attributes
     */
    public function setAttributes(array $attributes): Model
    {
        foreach ($attributes as $name => $value) {
            if (array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * Returns model attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the name of the model attribute
     */
    public function getAttributeName(string $key): string
    {
        if (array_key_exists($key, $this->attributeNames)) {
            $attributeName = $this->attributeNames[$key];
            if (is_array($attributeName)) {
                return $attributeName[lang()->getLanguage()];
            }
            return $attributeName;
        }
        return ucfirst($key);
    }

    /**
     * Returns the types of model attributes
     */
    public function getAttributeTypes(): array
    {
        return $this->attributeTypes;
    }

    /**
     * Returns the type of model attribute
     */
    public function getAttributeType(string $key): ?string
    {
        return $this->attributeTypes[$key] ?? null;
    }
}
