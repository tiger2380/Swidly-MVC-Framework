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
     * Create a new view instance.
     */
    public function __construct()
    {
        $this->compiler = new ComponentCompiler();
        $this->safeIncludePaths = [Swidly::theme()['base'] . '/views/'];
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

        // Start output buffering
        ob_start();

        // Include the view file
        include $viewPath;

        // Get the contents and clean the buffer
        $contents = ob_get_clean();

        // Compile components
        $components = $this->compiler->compile($contents);
        return $this->parseIncludes($this->parse($this->layout($components)));

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
        $this->component('alert', \Swidly\Components\Alert::class);
        // Add more common components here
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

        $pattern = '/{([a-zA-Z0-9_:]+)(?:,\s*default=([a-zA-Z0-9_:]+))?}/';

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
        $pattern = '/\{@include\s+[\'"]?([\w.\/-]+)[\'"]?\s*(.*?)}/';

        return preg_replace_callback($pattern, function ($matches) {
            $file = $matches[1];
            
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

            if (!empty($matches[2])) {
                // Option 1: Parse space-separated key=value pairs
                preg_match_all('/(\w+)=[\'"]?(.*?)[\'"]?(?:\s|$)/', $matches[2], $paramMatches, PREG_SET_ORDER);
                foreach ($paramMatches as $param) {
                    $params[$param[1]] = $param[2];
                }
            }

            if (file_exists($file)) {
                extract($params);
                ob_start();
                require_once $file;
                $content = ob_get_clean();

                return $content;
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
            if (str_starts_with($path, realpath($safePath))) {
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
                $content = ob_get_clean();

                //look for {{{children}}} placeholder and replace it
                $content = str_replace('{{{children}}}', $children, $content);
            }
        }
        return $content;
    }
}
