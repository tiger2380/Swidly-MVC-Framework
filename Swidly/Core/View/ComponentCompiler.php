<?php

declare(strict_types=1);

namespace Swidly\Core\View;

use Swidly\Core\Component;
use Swidly\Core\SwidlyException;

class ComponentCompiler
{
    /**
     * The components being used.
     *
     * @var array
     */
    protected array $components = [];

    /**
     * Create a new component compiler instance.
     */
    public function __construct()
    {
    }

    /**
     * Register a component.
     *
     * @param  string  $alias
     * @param  string  $class
     * @return void
     */
    public function register(string $alias, string $class): void
    {
        $this->components[$alias] = $class;
    }

    /**
     * Compile the component tags within the given template.
     *
     * @param  string  $value
     * @return string|null
     */
    public function compile(string $value): string|null
    {
                $pattern = <<<'REGEX'
        /
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        [\w\-:.@]+
                        (
                            =
                            (?:
                                "[^"]*"
                                |
                                '[^']*'
                                |
                                [^"\'=<>]+
                            )
                        )?
                    )*
                    \s*
                )
                (?<![\/])
            >
            (?<slot>(?:\s?.+)+)
        /x
        REGEX;

        return preg_replace_callback($pattern, function ($matches) {
            $component = $matches[1];

            // Parse attributes
            $attributesString = $matches['attributes'] ?? '';
            $attributes = $this->parseAttributes($attributesString);
            $attributes['slot'] = trim($matches['slot']) ?? '';

            // Get the component class
            $class = $this->components[$component] ?? null;
            if (!$class) {
                throw new SwidlyException("Component [{$component}] not found.");
            }

            // Create and render the component
            $instance = new $class($attributes);
            if (!($instance instanceof Component)) {
                throw new SwidlyException("Class [{$class}] must extend the Component class.");
            }
            ;
            return $instance->render();
        }, $value);
    }

    /**
     * Parse the component attributes.
     *
     * @param  string  $attributesString
     * @return array
     */
    protected function parseAttributes(string $attributesString): array
    {
        $attributes = [];
        
        $pattern = '/
            (?<name>[\w\-:.@]+)
            (
                =
                (?<value>
                    \"[^\"]*\"
                    |
                    \\\'[^\\\']*\\\'
                    |
                    [^\"\\\'=<>]+
                )
            )?
        /x';

        if (preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match['name'];
                $value = $match['value'] ?? null;

                if ($value === null) {
                    $attributes[$name] = true;
                } else {
                    $value = trim($value, '\'"');
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Check if a component is registered.
     *
     * @param  string  $alias
     * @return bool
     */
    public function has(string $alias): bool
    {
        return isset($this->components[$alias]);
    }

    /**
     * Get all registered components.
     *
     * @return array
     */
    public function getComponents(): array
    {
        return $this->components;
    }
}
