<?php

declare(strict_types=1);

namespace Swidly\Core\View;

class ComponentAttributes
{
    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Create a new component attributes instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get an attribute from the component.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @param  bool  $asWrapper
     * @return mixed|Attribute
     */
    public function get(string $key, mixed $default = null, bool $asWrapper = false): mixed
    {
        $value = $this->attributes[$key] ?? $default;
        
        if ($asWrapper && $value !== null) {
            return new Attribute($key, (string) $value, $this);
        }
        
        return $value;
    }

    /**
     * Determine if an attribute exists on the component.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get all of the attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set an attribute on the component.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Remove an attribute from the component.
     *
     * @param  string  $key
     * @return void
     */
    public function forget(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Render the attributes as an HTML string.
     *
     * @return string
     */
    public function render(): string
    {
        $html = [];

        foreach ($this->attributes as $key => $value) {
            if ($key === 'slot') {
                continue;
            }
            
            if (is_numeric($key)) {
                $html[] = $this->escape($value);
            } elseif ($value === true) {
                $html[] = $this->escape($key);
            } elseif ($value !== false && $value !== null) {
                $html[] = sprintf(
                    '%s="%s"',
                    $this->escape($key),
                    $this->escape($value)
                );
            }
        }

        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Escape HTML special characters in a string.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function escape(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map([$this, 'escape'], $value));
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Merge additional attributes / values into the component.
     *
     * @param  array  $attributes
     * @return static
     */
    public function merge(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Merge a value into a specific attribute.
     *
     * @param  string  $attribute
     * @param  string  $value
     * @param  string  $separator
     * @return static
     */
    public function mergeAttribute(string $attribute, string $value, string $separator = ' '): static
    {
        $existing = $this->get($attribute, '');
        
        // Combine existing and new values
        $combined = trim($existing . $separator . $value);
        
        // Remove duplicate values while preserving order (split by separator)
        $valueArray = array_unique(array_filter(explode($separator, $combined)));
        
        $this->set($attribute, implode($separator, $valueArray));

        return $this;
    }

    /**
     * Get the attributes as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
