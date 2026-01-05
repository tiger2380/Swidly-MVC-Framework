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
     * Resolve a component alias to a registered class.
     *
     * Tries several normalized forms so tags like `x:alert`, `x-alert`,
     * or `x.alert` map to a registered alias.
     *
     * @param string $component
     * @return string|null
     */
    protected function resolveComponent(string $component): ?string
    {
        // Try exact
        if (isset($this->components[$component])) {
            return $this->components[$component];
        }

        // Normalizations to try
        $variants = [];

        // replace dots with dashes: alert.custom -> alert-custom
        $variants[] = str_replace('.', '-', $component);
        // replace colons with dashes: alert:custom -> alert-custom
        $variants[] = str_replace(':', '-', $component);
        // replace both colons and dots with dashes
        $variants[] = str_replace(['.', ':'], '-', $component);
        // try lowercase forms
        $variants[] = strtolower($component);
        $variants[] = strtolower(str_replace(['.', ':'], '-', $component));

        foreach ($variants as $v) {
            if (isset($this->components[$v])) {
                return $this->components[$v];
            }
        }

        // Fallback: if component includes a dot, try the base name (alert.custom -> alert)
        if (strpos($component, '.') !== false) {
            $base = strstr($component, '.', true);
            if ($base && isset($this->components[$base])) {
                return $this->components[$base];
            }
        }

        return null;
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
     * @param  array   $context
     * @return string|null
     */
    public function compile(string $value, array $context = []): string|null
    {
        $pattern = <<<'REGEX'
        /
            <
                \s*
                x[-:]([\w\-:\.]+)
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
                                [^"'=<>]+
                            )
                        )?
                    )*
                    \s*
                )
                (?:
                    \/>
                    |
                    >
                        (?<slot>[\s\S]*?)
                        <\/x[-:]\1>
                )
            
        /x
        REGEX;

        return preg_replace_callback($pattern, function ($matches) use ($context) {
            $component = $matches[1];

            // Parse attributes
            $attributesString = $matches['attributes'] ?? '';
            $attributes = $this->parseAttributes($attributesString, $context);
            $attributes['slot'] = isset($matches['slot']) ? trim($matches['slot']) : '';

            // Get the component class
            $class = $this->resolveComponent($component);
            if (!$class) {
                dd("Component [{$component}] not found.");
            }

            // Create and render the component
            $instance = new $class($attributes);
            if (!($instance instanceof Component)) {
                throw new SwidlyException("Class [{$class}] must extend the Component class.");
            }
            ;
            $rendered = $instance->render();
            
            // Recursively compile any nested components in the rendered output
            return $this->compile($rendered, $context);
        }, $value);
    }

    /**
     * Parse the component attributes.
     *
     * @param  string  $attributesString
     * @param  array   $context
     * @return array
     */
    protected function parseAttributes(string $attributesString, array $context = []): array
    {
        $attributes = [];
        $props = [];
        
        $pattern = '/
            (?<name>[\w\-:.@]+)
            (
                =
                (?<value>
                    \"[^\"]*\"
                    |
                    \\\'[^\\\']*\\\'
                    |
                    [^\s\"\\\'=<>]+
                )
            )?
        /x';

        if (preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rawName = $match['name'];
                $value = $match['value'] ?? null;

                // Handle @prop attributes separately
                if (str_starts_with($rawName, '@prop')) {
                    // Extract the prop name from @prop:name or @prop(name)
                    if (preg_match('/@prop\(([^)]+)\)|@prop:(.+)/', $rawName, $propMatch)) {
                        $propName = $propMatch[1] ?? $propMatch[2];
                        
                        if ($value === null) {
                            $props[$propName] = true;
                        } else {
                            $cleanValue = trim($value, '\'"');
                            
                            // Check if it's a variable reference (e.g., $variableName or $variable)
                            if (preg_match('/^\$(\w+)$/', $cleanValue, $varMatch)) {
                                $varName = $varMatch[1];
                                $props[$propName] = $context[$varName] ?? null;
                            }
                            // Check if it's a bound variable with : prefix (starts with :)
                            elseif (str_starts_with($cleanValue, ':')) {
                                $cleanValue = substr($cleanValue, 1); // Remove the : prefix
                                
                                // Apply type coercion like regular bound attributes
                                if (strcasecmp($cleanValue, 'null') === 0) {
                                    $props[$propName] = null;
                                } elseif (strcasecmp($cleanValue, 'true') === 0) {
                                    $props[$propName] = true;
                                } elseif (strcasecmp($cleanValue, 'false') === 0) {
                                    $props[$propName] = false;
                                } elseif (is_numeric($cleanValue)) {
                                    $props[$propName] = (strpos($cleanValue, '.') !== false) ? (float) $cleanValue : (int) $cleanValue;
                                } elseif ($cleanValue !== '' && ($cleanValue[0] === '{' || $cleanValue[0] === '[')) {
                                    $decoded = json_decode($cleanValue, true);
                                    $props[$propName] = json_last_error() === JSON_ERROR_NONE ? $decoded : $cleanValue;
                                } else {
                                    $props[$propName] = $cleanValue;
                                }
                            }
                            // Default: treat as string
                            else {
                                $props[$propName] = $cleanValue;
                            }
                        }
                    }
                    continue;
                }

                // Detect bound attributes like :prop="..." or bind:prop="..."
                $bound = false;
                if (str_starts_with($rawName, ':')) {
                    $bound = true;
                    $name = substr($rawName, 1);
                } elseif (str_starts_with($rawName, 'bind:')) {
                    $bound = true;
                    $name = substr($rawName, 5);
                } else {
                    $name = $rawName;
                }

                if ($value === null) {
                    $attributes[$name] = true;
                    continue;
                }

                // Trim surrounding quotes
                $value = trim($value, '\'"');

                // Coerce common literal types
                if (strcasecmp($value, 'null') === 0) {
                    $typed = null;
                } elseif (strcasecmp($value, 'true') === 0) {
                    $typed = true;
                } elseif (strcasecmp($value, 'false') === 0) {
                    $typed = false;
                } elseif (is_numeric($value)) {
                    // preserve ints vs floats
                    $typed = (strpos($value, '.') !== false) ? (float) $value : (int) $value;
                } elseif ($bound && ($value !== '' && ($value[0] === '{' || $value[0] === '['))) {
                    // Try to decode JSON for bound structured values
                    $decoded = json_decode($value, true);
                    $typed = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
                } else {
                    $typed = $value;
                }

                $attributes[$name] = $typed;
            }
        }

        // Add props to attributes under a special key
        if (!empty($props)) {
            $attributes['__props'] = $props;
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
