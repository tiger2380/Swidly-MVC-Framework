<?php

namespace Swidly\Core\Commands;

class ComponentCommand extends AbstractCommand 
{
    private const COMPONENT_TEMPLATE = <<<'STR'
<?php

declare(strict_types=1);

namespace Swidly\themes\%s\components;

use Swidly\Core\Component;

class %s extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->initializeProperties($attributes);
    }

    /**
     * Render the component.
     *
     * @return string
     */
    public function render(): string
    {
        $slot = $this->getAttribute('slot', '');

        return <<<HTML
        <div {$this->attributes}>{$slot}</div>
        HTML;
    }
}
STR;

    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        
        if (empty($name)) {
            throw new \InvalidArgumentException("Component name is required");
        }

        // Ensure components directory exists
        $componentsDir = $theme['base'] . '/components';
        if (!is_dir($componentsDir)) {
            mkdir($componentsDir, 0755, true);
        }

        $componentPath = sprintf(
            '%s/%s.php',
            $componentsDir,
            ucfirst($name)
        );
        
        if (file_exists($componentPath)) {
            throw new \RuntimeException("Component already exists: $componentPath");
        }

        formatPrintLn(['cyan', 'bold'], "Creating component...");
        $content = sprintf(self::COMPONENT_TEMPLATE, $theme['name'], ucfirst($name));
        file_put_contents($componentPath, $content);
        
        formatPrintLn(['green', 'bold'], "Component created successfully: " . ucfirst($name));
        
        // Convert PascalCase to kebab-case for usage hint
        $alias = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        formatPrintLn(['yellow'], "Usage: <x-{$alias}>content</x-{$alias}>");
    }
}
