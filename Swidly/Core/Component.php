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
     * The view instance.
     *
     * @var View
     */
    protected View $view;

    /**
     * Create a new component instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = new ComponentAttributes($attributes);
        $this->view = View::getInstance();
        $this->view->registerCommonComponents();
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
    public function getAttribute(string $key, mixed $default = null, bool $asWrapper = false): mixed
    {
        return $this->attributes->get($key, $default, $asWrapper);
    }

    /**
     * Get an attribute as a wrapper for fluent manipulation.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return View\Attribute
     */
    public function attr(string $key, mixed $default = ''): View\Attribute
    {
        return $this->attributes->get($key, $default, true);
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

    protected function mergeAttributes(array $additionalAttributes): void
    {
        $this->attributes->merge($additionalAttributes);
    }

    /**
     * Merge a value into a specific attribute.
     *
     * @param  string  $attribute
     * @param  string  $value
     * @param  string  $separator
     * @return $this
     */
    public function mergeAttribute(string $attribute, string $value, string $separator = ' '): static
    {
        $this->attributes->mergeAttribute($attribute, $value, $separator);
        return $this;
    }

    /**
     * Merge classes into the class attribute.
     *
     * @param  string  $classes
     * @return $this
     */
    public function mergeClass(string $classes): static
    {
        $this->attributes->mergeAttribute('class', $classes);
        return $this;
    }

    public function getProp(string $key, mixed $default = null): mixed
    {
        if (!isset($this->attributes['__props'])) {
            return $default;
        }
        return $this->attributes['__props'][$key] ?? $default;
    }

    public function getProps(): array
    {
        return $this->attributes['__props'] ?? [];
    }
}
