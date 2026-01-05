<?php

declare(strict_types=1);

namespace Swidly\Core\View;

class Attribute
{
    /**
     * The attribute value.
     *
     * @var string
     */
    protected string $value;

    /**
     * The attribute name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The component attributes instance.
     *
     * @var ComponentAttributes
     */
    protected ComponentAttributes $attributes;

    /**
     * Create a new attribute instance.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  ComponentAttributes  $attributes
     */
    public function __construct(string $name, string $value, ComponentAttributes $attributes)
    {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
    }

    /**
     * Merge a value into this attribute.
     *
     * @param  string  $value
     * @param  string  $separator
     * @return $this
     */
    public function merge(string $value, string $separator = ' '): static
    {
        // Combine existing and new values
        $combined = trim($this->value . $separator . $value);
        
        // Remove duplicate values while preserving order
        $valueArray = array_unique(array_filter(explode($separator, $combined)));
        
        $this->value = implode($separator, $valueArray);
        
        // Update the component attributes
        $this->attributes->set($this->name, $this->value);

        return $this;
    }

    /**
     * Append a value to this attribute.
     *
     * @param  string  $value
     * @param  string  $separator
     * @return $this
     */
    public function append(string $value, string $separator = ' '): static
    {
        $this->value = trim($this->value . $separator . $value);
        $this->attributes->set($this->name, $this->value);

        return $this;
    }

    /**
     * Prepend a value to this attribute.
     *
     * @param  string  $value
     * @param  string  $separator
     * @return $this
     */
    public function prepend(string $value, string $separator = ' '): static
    {
        $this->value = trim($value . $separator . $this->value);
        $this->attributes->set($this->name, $this->value);

        return $this;
    }

    /**
     * Get the attribute value.
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get the attribute value as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
