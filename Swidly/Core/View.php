<?php

declare(strict_types=1);

namespace Swidly\Core;

use Swidly\Core\View\ComponentCompiler;
use Swidly\Core\SwidlyException;

class View
{
    /**
     * The component compiler instance.
     *
     * @var ComponentCompiler
     */
    protected ComponentCompiler $compiler;

    /**
     * The safe include paths.
     *
     * @var array
     */
    protected array $safeIncludePaths = [];

    /**
     * The view data.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * The sections data.
     *
     * @var array
     */
    protected array $sections = [];

    /**
     * The current section being captured.
     *
     * @var string|null
     */
    protected ?string $currentSection = null;

    /**
     * Create a new view instance.
     */
    public function __construct()
    {
        $this->compiler = new ComponentCompiler();
        $this->safeIncludePaths = [Swidly::theme()['base'] . '/views/'];
        
        // Make view instance globally accessible
        $GLOBALS['__view'] = $this;
    }

    /**
     * Register a component.
     *
     * @param  string  $alias
     * @param  string  $class
     * @return void
     */
    public function component(string $alias, string $class): void
    {
        $this->compiler->register($alias, $class);
    }

    /**
     * Set view data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function with(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Render a view.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        $this->with($data);

        $viewPath = $this->findView($view); 
      
        if (!$viewPath) {
            throw new SwidlyException("View [{$view}] not found.");
        }
      
        // Extract data to make it available in the view
        extract($this->data);

        ob_start();
        require $viewPath;
        $contents = ob_get_clean();

        if ($contents === false) {
            $contents = '';
        }

        // Compile components
        $components = $this->compiler->compile($contents);
        $parsed = $this->parseIncludes($components);
        $evaluated = $this->evaluatePhpExpressions($parsed);
        $finalContent = $this->layout($evaluated);
        return $finalContent;
    }

    /**
     * Parse PHP expressions in template syntax.
     * Converts {{ expression }} to <?= expression; ?>
     * Converts {!! expression !!} to <?= expression; ?> (unescaped)
     * Converts {{-- comment --}} to PHP comments
     *
     * @param  string  $content
     * @return string
     */
    protected function parsePhpExpressions(string $content): string
    {
        // Remove template comments {{-- comment --}}
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);

        // Replace yield() calls with yieldSection() since yield is a reserved keyword
        $content = preg_replace_callback(
            '/\byield\s*\(/i',
            function ($matches) {
                return 'yieldSection(';
            },
            $content
        );

        // Parse unescaped expressions {!! expression !!} (raw output, no escaping)
        $content = preg_replace_callback(
            '/\{!!\s*(.+?)\s*!!\}/s',
            function ($matches) {
                $expression = trim($matches[1]);
                return "<?= {$expression}; ?>";
            },
            $content
        );

        // Parse escaped expressions {{ expression }} (HTML escaped)
        $content = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($matches) {
                $expression = trim($matches[1]);
                return "<?= htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8'); ?>";
            },
            $content
        );

        return $content;
    }

    /**
     * Compile and evaluate PHP expressions in content.
     *
     * @param  string  $content
     * @return string
     */
    protected function evaluatePhpExpressions(string $content): string
    {
        // Parse the template syntax to PHP
        $phpContent = $this->parsePhpExpressions($content);

        // Extract data to make it available
        extract($this->data);

        // Evaluate the PHP content
        ob_start();
        try {
            eval('?>' . $phpContent);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new SwidlyException("Template evaluation error: " . $e->getMessage());
        }
        return ob_get_clean();
    }

    /**
     * Find a view file.
     *
     * @param  string  $view
     * @return string|null
     */
    protected function findView(string $view): ?string
    {
        $themePath = Swidly::theme()['base'] ?? '';
        if (empty($themePath)) {
            throw new SwidlyException("Theme path not defined.");
        }

        // Replace dots with directory separators
        $view = str_replace('.', '/', $view);

        // Look in the theme's views directory
        $viewPath = $themePath . '/views/' . $view . '.php';

        if (file_exists($viewPath)) {
            return $viewPath;
        }

        return null;
    }

    /**
     * Register common components.
     *
     * @return void
     */
    public function registerCommonComponents(): void
    {
        //$this->component('alert', \Swidly\Components\Alert::class);
        // Add more common components here
        
        // Auto-load theme components
        $this->loadThemeComponents();
    }

    /**
     * Auto-load all components from the theme's components directory.
     *
     * @return void
     */
    protected function loadThemeComponents(): void
    {
        $theme = Swidly::theme();
        $componentsPath = $theme['base'] . '/components';
        
        if (!is_dir($componentsPath)) {
            return;
        }

        $files = glob($componentsPath . '/*.php');
        
        foreach ($files as $file) {
            $fileName = basename($file, '.php');
            
            // Convert PascalCase to kebab-case for component alias
            $alias = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $fileName));
            
            // Build the full class name
            $namespace = $theme['namespace'] ?? 'Swidly\\themes\\' . basename($theme['base']);
            $className = $namespace . '\\components\\' . $fileName;
            
            // Register the component if the class exists
            if (class_exists($className)) {
                $this->component($alias, $className);
            }
        }
    }

    /**
     * Parse template variables in a string
     * @param string|null $str String to parse
     * @return array|string|null Parsed string
     */
    protected function parse(?string $str): array|string|null
    {
        if ($str === null) {
            return null;
        }

        $pattern = '/{{([a-zA-Z0-9_:]+)(?:,\s*default=([a-zA-Z0-9_:]+))?}}/';

        $result = preg_replace_callback($pattern, function ($matches) {
            $key = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            $default = isset($matches[2]) ? htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') : null;
            
            $word = $this->data[$key] ?? ($this->data['lang'][$key] ?? $default ?? null);
            return isset($word) ? $this->parse($word) : '';
        }, $str);

        return $result === null ? '' : $result;
    }

    /**
     * Load language file based on request or default configuration
     * @throws SwidlyException If language file is invalid or corrupted
     */
    protected function getLanguage(): void
    {
        $allowedLangs = ['en', 'es']; // Add supported languages
        $default_lang = (new Request())->get('lang', null, null, Swidly::getConfig('default_lang'));
        
        // Validate language code
        if (!in_array($default_lang, $allowedLangs, true)) {
            $default_lang = 'en'; // Fallback to English
        }

        $lang_path = __DIR__ . "/../lang/{$default_lang}.json";

        if (!file_exists($lang_path)) {
            throw new SwidlyException("Language file not found for: {$default_lang}", 404);
        }

        $string = file_get_contents($lang_path);
        if ($string === false) {
            throw new SwidlyException("Failed to read language file: {$default_lang}", 500);
        }

        $lang_data = json_decode($string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SwidlyException("Invalid language file format: {$default_lang}", 500);
        }

        $this->with('lang', $lang_data);
    }

    /**
     * Parse and process include directives in templates
     * @param string $str Template string to parse
     * @return string Processed template string
     * @throws SwidlyException If included file path is invalid or file not found
     */
    public function parseIncludes(string $str): string
    {
        $str = $this->parse($str);
        $pattern = '/\{@include\s+([\'"])?([^\'"]+)\1\s*(.*?)}/';
        return preg_replace_callback($pattern, function ($matches) {
            $file = $matches[2];
            $params = [];

            if (!empty($matches[3])) {
                // Option 1: Parse space-separated key=value pairs
                preg_match_all('/(\w+)=[\'"]?(.*?)[\'"]?(?:\s|$)/', $matches[3], $paramMatches, PREG_SET_ORDER);
                foreach ($paramMatches as $param) {
                    $params[$param[1]] = $param[2];
                }
            }

            // Prevent path traversal
            if (preg_match('/\.\.\/|\.\.\\\\/', $file)) {
                throw new SwidlyException('Invalid include path', 400);
            }

            $base = Swidly::theme()['base'];
            if (!$base) {
                throw new SwidlyException('Theme base path not set', 500);
            }

            $file = $base . '/views/' . ltrim($file, '/') . '.php';

            // Verify file is within allowed paths
            $realPath = realpath($file);
            if (!$realPath || !$this->isPathInSafeDirectories($realPath)) {
                throw new SwidlyException('Invalid include path', 400);
            }

            $params = [];

            if (!empty($matches[3])) {
                // Option 1: Parse space-separated key=value pairs
                preg_match_all('/(\w+)=[\'"]?(.*?)[\'"]?(?:\s|$)/', $matches[3], $paramMatches, PREG_SET_ORDER);
                foreach ($paramMatches as $param) {
                    $params[$param[1]] = $param[2];
                }
            }

            if (file_exists($file)) {
                extract($params);
                extract($this->data);
                ob_start();
                require $file;
                $content = ob_get_clean();

                return $content !== false ? $content : '';
            }

            return '';
        }, $str);
    }

    /**
     * Check if a file path is within the allowed safe directories
     * @param string $path Path to check
     * @return bool True if path is safe, false otherwise
     */
    protected function isPathInSafeDirectories(string $path): bool
    {
        foreach ($this->safeIncludePaths as $safePath) {
            $realSafePath = realpath($safePath);
            if ($realSafePath !== false && str_starts_with($path, $realSafePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse layout blocks and apply them to content
     *
     * @param  string  $content
     * @return string
     */
    protected function layout(string $content): string {
        // Step 1: Extract all layout blocks
        $layoutPattern = '/(?s)\{@layout\s*(?P<attributes>[^}]*)\}\s*(?P<children>.*?)\s*\{@endlayout\}/';
        preg_match_all($layoutPattern, $content, $matches, PREG_SET_ORDER);

        $layouts = [];

        foreach ($matches as $m) {
            $attrString = trim($m['attributes']);
            $children   = trim($m['children']);

            // Step 2: Parse attributes into key/value pairs
            $attrs = [];

            // This pattern supports: key="value", key='value', key=value, key={json}, flag (true)
            $attrPattern = '/
                (\w+)                     
                (?:\s*=\s*                    
                    (?:
                        "([^"]*)"             
                        |\'([^\']*)\'          
                        |(\{[^}]*\})           
                        |([^\s]+)         
                    )
                )?
            /x';

            preg_match_all($attrPattern, $attrString, $attrMatches, PREG_SET_ORDER);

            foreach ($attrMatches as $a) {
                $key = $a[1];
                $value = null;

                // Determine which capture group matched
                if (!empty($a[2])) {
                    $value = $a[2]; // double quotes
                } elseif (!empty($a[3])) {
                    $value = $a[3]; // single quotes
                } elseif (!empty($a[4])) {
                    $value = json_decode($a[4], true) ?? $a[4]; // try decode JSON-like values
                } elseif (!empty($a[5])) {
                    $value = $a[5]; // unquoted
                } else {
                    $value = true; // flag attribute, like "visible"
                }

                $attrs[$key] = $value;
            }

            $layouts[] = [
                'attributes' => $attrs,
                'children'   => $children,
            ];
        }

        // Step 3: Apply layouts in reverse order (innermost first)
        foreach (array_reverse($layouts) as $layout) {
            $attrs = $layout['attributes'];
            $children = $layout['children'];
            $file = $attrs['file'] ?? 'layout';
            $layoutFile = Swidly::theme()['base'] . '/views/' . ltrim($file, '/') . '.php';
            if (file_exists($layoutFile)) {
                extract($attrs);
                ob_start();
                require $layoutFile;
                $layoutContent = ob_get_clean();

                //look for {{{children}}} placeholder and replace it
                $content = str_replace('{{{children}}}', $children, $layoutContent !== false ? $layoutContent : '');
            }
        }
        return $content;
    }

    /**
     * Start a new section.
     *
     * @param  string  $section
     * @return void
     */
    public function section(string $section): void
    {
        $this->currentSection = $section;
        ob_start();
    }

    /**
     * End the current section.
     *
     * @return void
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new SwidlyException('Cannot end section without starting one.');
        }

        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content !== false ? $content : '';
        $this->currentSection = null;
    }

    /**
     * Yield the content for a section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yield(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }
}
