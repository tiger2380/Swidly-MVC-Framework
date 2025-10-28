<?php

declare(strict_types=1);

namespace Swidly\Core;

use Swidly\Core\View\ComponentAttributes;

abstract class Component
{
    /**
     * The cache of public property names.
     *
     * @var array
     */
    protected static array $propertyCache = [];

    /**
     * The component attributes.
     *
     * @var ComponentAttributes
     */
    public ComponentAttributes $attributes;

    /**
     * Create a new component instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = new ComponentAttributes($attributes);
        $this->setUp();
    }

    /**
     * Set up the component.
     *
     * @return void
     */
    protected function setUp(): void
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Render the component as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Initialize the component's properties from the attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function initializeProperties(array $attributes): void
    {
        $class = get_class($this);

        if (!isset(static::$propertyCache[$class])) {
            static::$propertyCache[$class] = array_column(
                (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC),
                'name'
            );
        }

        foreach (static::$propertyCache[$class] as $property) {
            if (array_key_exists($property, $attributes)) {
                $this->{$property} = $attributes[$property];
            }
        }
    }

    /**
     * Get an attribute from the component.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * Check if the component has a specific attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return $this->attributes->has($key);
    }

    /**
     * Get the attributes as an array.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes->getAttributes();
    }

    /**
     * Get the attributes as a string.
     *
     * @return string
     */
    public function attributesToString(): string
    {
        return $this->attributes->render();
    }

    /**
     * Compile the component attributes to HTML attributes.
     *
     * @return string
     */
    protected function compileAttributes(): string
    {
        return $this->attributes->render();
    }
}
