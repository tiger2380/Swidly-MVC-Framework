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
     * The layout to extend.
     *
     * @var string|null
     */
    protected ?string $extendsLayout = null;

    /**
     * The current slot being captured.
     *
     * @var string|null
     */
    protected ?string $currentSlot = null;

    /**
     * The stacks (push sections) data.
     *
     * @var array
     */
    protected array $stacks = [];

    /**
     * The current stack being captured.
     *
     * @var string|null
     */
    protected ?string $currentStack = null;

    /**
     * Unique identifier for this view instance.
     *
     * @var string
     */
    protected string $instanceId;

    protected static ?View $instance = null;

    protected bool $isChild = false;

    protected array $directiveStackPlaceholders = [];

    protected string $finalContent = '';
    protected array $allData = [];
    protected array $contents = [];

    /**
     * Create a new view instance.
     */
    public function __construct()
    {
        $this->instanceId = uniqid('view_', true);
        $this->compiler = new ComponentCompiler();
        $this->safeIncludePaths = [Swidly::theme()['base'] . '/views/'];
        $this->directiveStackPlaceholders = [];
        // Make view instance globally accessible
        $GLOBALS['__view'] = $this;
    }

    /**
     * Get the unique instance ID.
     *
     * @return string
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public static function getInstance(): self
    {;
        if (self::$instance === null) {
            self::$instance = new self();
        } else {
            self::$instance->isChild = true;
        }

        return self::$instance;
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
     */
    public function render(string $view, array $data = [])
    {;
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

        // Check if this view extends a layout
        if ($this->extendsLayout !== null) {
            $layoutPath = $this->findView($this->extendsLayout);
            if (!$layoutPath) {
                throw new SwidlyException("Layout [{$this->extendsLayout}] not found.");
            }
            
            // Reset extends to prevent infinite loop
            $this->extendsLayout = null;
            
            // Render the layout (which will have access to the sections we just defined)
            ob_start();
            require $layoutPath;
            $contents = ob_get_clean();
            
            if ($contents === false) {
                $contents = '';
            }
        }

        // Recursively compile components and parse directives to handle nested components
        $maxIterations = 10; // Prevent infinite loops
        $iteration = 0;
        $previousContent = '';

        while ($contents !== $previousContent && $iteration < $maxIterations) {
            $previousContent = $contents;

            // Compile components (which may output @push/@stack directives)
            $contents = $this->compiler->compile($contents);
            $iteration++;
        }

        // After evaluation, all @push directives have been executed and stacks are populated
        // Now replace stack placeholders with actual content
        $this->getLanguage();
        // Evaluate PHP expressions (this executes @push directives, populating stacks)
        if (!$this->isChild) {
            $this->data = array_merge($this->data, ...array_column($GLOBALS['__view']->contents, 'data'));
            $contents = $this->parseDirectives($contents);
            $contents = $this->evaluatePhpExpressions($contents);
            $contents = $this->replaceYieldPlaceholders($contents);

            return $this->replaceStackPlaceholders($contents);
        } else {
            $GLOBALS['__view']->contents[] = ['content' => $contents, 'data' => get_defined_vars()['data'] ?? $this->data];
            return $contents;
        }
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

        // Parse unescaped expressions {!! expression !!} (raw output, no escaping)
        $content = preg_replace_callback(
            '/\{!!\s*(.+?)\s*!!\}/s',
            function ($matches) {
                $expression = trim($matches[1]);
                // Decode HTML entities that may have been encoded earlier
                $expression = html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return "<?= {$expression}; ?>";
            },
            $content
        );

        // Parse escaped expressions {{ expression }} (HTML escaped)
        $content = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($matches) {
                $expression = trim($matches[1]);
                // Decode HTML entities that may have been encoded earlier
                $expression = html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return "<?= htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8'); ?>";
            },
            $content
        );

        return $content;
    }

    /**
     * Parse template expressions in an array recursively.
     * Processes any string containing {{ }} or {!! !!} expressions.
     *
     * @param  array  $array
     * @return array
     */
    public function parseArrayExpressions(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursively parse nested arrays
                $array[$key] = $this->parseArrayExpressions($value);
            } elseif (is_string($value) && (str_contains($value, '{{') || str_contains($value, '{!!'))) {
                // Only parse strings that contain template expressions
                $array[$key] = $this->parsePhpExpressions($value);
            }
        }
        
        return $array;
    }

    /**
     * Evaluate PHP expressions in an array recursively.
     * Processes strings containing PHP code tags.
     *
     * @param  array  $array
     * @return array
     */
    public function evaluateArrayExpressions(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursively evaluate nested arrays
                $array[$key] = $this->evaluateArrayExpressions($value);
            } elseif (is_string($value) && str_contains($value, '<?')) {
                // Only evaluate strings that contain PHP code
                $array[$key] = $this->evaluatePhpExpressions($value);
            }
        }
        
        return $array;
    }

    /**
     * Compile and evaluate PHP expressions in content.
     *
     * @param  string  $content
     * @return string
     */
    protected function evaluatePhpExpressions(string $content): string
    {        
        // Extract data to make it available
        extract($this->data);
        // Evaluate the PHP content

        ob_start();
        try {
            eval('?>' . $content);
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
        $request = new Request();
        $langParam = $request->get('lang');

        if ($langParam !== null && in_array($langParam, $allowedLangs, true)) {
            // User explicitly switched language — save to session
            Store::save('lang', $langParam);
            $default_lang = $langParam;
        } else {
            // Check session, then fall back to config default
            $default_lang = Store::get('lang', Swidly::getConfig('default_lang'));
        }

        // Validate language code
        if (!in_array($default_lang, $allowedLangs, true)) {
            $default_lang = 'en'; // Fallback to English
        }

        $themePath = Swidly::theme()['base'] ?? '';
        $lang_path = $themePath . "/lang/{$default_lang}.json";

        // Fall back to core lang folder if not found in theme
        if (!file_exists($lang_path)) {
            $lang_path = __DIR__ . "/../lang/{$default_lang}.json";
        }

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
     * @deprecated Use components instead of includes
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
                // Read the file content and parse directives first
                $fileContent = file_get_contents($file);
                if ($fileContent === false) {
                    return '';
                }
                
                // Parse directives (including @push/@endpush) before evaluation
                $parsedContent = $this->parseDirectives($fileContent);
                
                // Now evaluate the parsed content
                extract($params);
                extract($this->data);
                ob_start();
                try {
                    eval('?>' . $parsedContent);
                } catch (\Throwable $e) {
                    ob_end_clean();
                    throw new SwidlyException("Include evaluation error in {$file}: " . $e->getMessage());
                }
                $content = ob_get_clean();

                return $content !== false ? $content : '';
            }

            return '';
        }, $str);
    }

    /**
     * Extract content between balanced parentheses
     * @param string $str String starting after the opening parenthesis
     * @param int $offset Offset to start searching from
     * @return array ['content' => string, 'endPos' => int]
     */
    protected function extractBalancedParentheses(string $str, int $offset): array
    {
        $depth = 1;
        $content = '';
        $pos = $offset;
        $inString = false;
        $stringChar = null;
        $escaped = false;

        while ($pos < strlen($str) && $depth > 0) {
            $char = $str[$pos];

            // Handle escape sequences
            if ($escaped) {
                $content .= $char;
                $escaped = false;
                $pos++;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $content .= $char;
                $pos++;
                continue;
            }

            // Handle string literals
            if (($char === '"' || $char === "'") && !$inString) {
                $inString = true;
                $stringChar = $char;
                $content .= $char;
            } elseif ($char === $stringChar && $inString) {
                $inString = false;
                $stringChar = null;
                $content .= $char;
            } elseif (!$inString) {
                // Only count parentheses outside of strings
                if ($char === '(') {
                    $depth++;
                    $content .= $char;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth > 0) {
                        $content .= $char;
                    }
                } else {
                    $content .= $char;
                }
            } else {
                $content .= $char;
            }

            $pos++;
        }

        return ['content' => trim($content), 'endPos' => $pos];
    }

    /**
     * Parse template directives like @csrf
     * @param string $str Template string to parse
     * @return string Processed template string
     */
    protected function parseDirectives(string $str): string
    {
        $str = $this->parsePhpExpressions($str);
        // Parse @csrf directive
        $str = preg_replace(
            '/@csrf\b/',
            '<input type="hidden" name="csrf" value="<?= \\Swidly\\Core\\Store::csrf() ?>">',
            $str
        );

        // Parse @captcha directive - renders a math challenge that blocks form submission until correct
        $str = preg_replace(
            '/@captcha\b/',
            '<?php \\Swidly\\Core\\Store::generateCaptcha(); ?>' .
            '<div class="captcha-group" style="margin:0.5rem 0;">' .
            '<label class="form-label" style="display:block;margin-bottom:0.25rem;">' .
            '<?= htmlspecialchars(\\Swidly\\Core\\Store::get(\'_captcha_question\', \'\'), ENT_QUOTES, \'UTF-8\') ?></label>' .
            '<input type="number" name="_captcha_answer" class="form-control" required autocomplete="off" ' .
            'data-captcha-expected="<?= \\Swidly\\Core\\Store::get(\'_captcha_answer\') ?>" ' .
            'style="max-width:120px;display:inline-block;" />' .
            '<span class="captcha-status" style="margin-left:0.5rem;"></span>' .
            '<input type="hidden" name="_captcha_hash" value="<?= \\Swidly\\Core\\Store::get(\'_captcha_hash\', \'\') ?>" />' .
            '</div>' .
            '<script>' .
            '(function(){' .
            'document.querySelectorAll(\'input[name="_captcha_answer"]\').forEach(function(input){' .
            'if(input.dataset.captchaListenerAttached)return;' .
            'input.dataset.captchaListenerAttached="1";' .
            'var form=input.closest("form");' .
            'var status=input.closest(".captcha-group").querySelector(".captcha-status");' .
            'var expected=parseInt(input.dataset.captchaExpected,10);' .
            'function check(){' .
            'var val=parseInt(input.value,10);' .
            'if(isNaN(val)){status.textContent="";return false;}' .
            'if(val===expected){status.textContent="\u2713";status.style.color="green";return true;}' .
            'status.textContent="\u2717";status.style.color="red";return false;' .
            '}' .
            'input.addEventListener("input",check);' .
            'if(form){form.addEventListener("submit",function(e){' .
            'if(!check()){e.preventDefault();input.focus();}' .
            '});}' .
            '});' .
            '})();' .
            '</script>',
            $str
        );

        // Parse @extends directive for layout inheritance
        $str = preg_replace_callback(
            '/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            function ($matches) {
                return '<?php $GLOBALS[\'__view\']->extend(\'' . addslashes($matches[1]) . '\'); ?>';
            },
            $str
        );

        $str = preg_replace_callback(
            '/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"])?\s*\)/',
            function ($matches) {
                $section = addslashes($matches[1]);
                if (isset($matches[2])) {
                    $value = addslashes($matches[2]);
                    return '<?php $GLOBALS[\'__view\']->section(\'' . $section . '\', \'' . $value . '\'); ?>';
                }
                return '<?php $GLOBALS[\'__view\']->section(\'' . $section . '\'); ?>';
            },
            $str
        );

        $str = preg_replace(
            '/@endsection\b/',
            '<?php $GLOBALS[\'__view\']->endSection(); ?>',
            $str
        );

        // Parse @yield block form: @yield('name') ... @endyield (content between is default)
        // Uses [^@]*(?:@(?!yield\b|endyield\b)[^@]*)* to efficiently match content without backtracking issues
        $str = preg_replace_callback(
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)([^@]*(?:@(?!yield\b|endyield\b)[^@]*)*)@endyield/s',
            function ($matches) {
                $section = addslashes($matches[1]);
                $encoded = base64_encode($matches[2]);
                return '__VIEW_YIELD_PLACEHOLDER__' . $section . '__BLOCK_DEFAULT__' . $encoded . '__';
            },
            $str
        ) ?? $str;

        // Parse @yield inline directive to output section content
        $str = preg_replace_callback(
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]*)[\'"])?\s*\)/',
            function ($matches) {
                $section = addslashes($matches[1]);
                $default = isset($matches[2]) ? addslashes($matches[2]) : '';

                return '__VIEW_YIELD_PLACEHOLDER__' . $section . '__DEFAULT__' . $default . '__';
            },
            $str
        );

        // Parse @method directive for HTTP method spoofing
        $str = preg_replace_callback(
            '/@method\s*\(\s*[\'"](\w+)[\'"]\s*\)/',
            function ($matches) {
                $method = strtoupper($matches[1]);
                return '<input type="hidden" name="_method" value="' . $method . '">';
            },
            $str
        );

        // Parse @auth / @endauth directives
        $str = preg_replace(
            '/@auth\b/',
            '<?php if (\\Swidly\\Middleware\\AuthMiddleware::check()): ?>',
            $str
        );
        $str = preg_replace('/@endauth\b/', '<?php endif; ?>', $str);

        // Parse @guest / @endguest directives
        $str = preg_replace(
            '/@guest\b/',
            '<?php if (!(\\Swidly\\Middleware\\AuthMiddleware::check())): ?>',
            $str
        );
        $str = preg_replace('/@endguest\b/', '<?php endif; ?>', $str);

        // Parse @if / @elseif / @else / @endif directives with proper parentheses matching
        $offset = 0;
        while (preg_match('/@if\s*\(/s', $str, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $matchStart = (int)$matches[0][1];
            $matchLen = strlen($matches[0][0]);
            $pos = $matchStart + $matchLen;
            $result = $this->extractBalancedParentheses($str, $pos);
            
            // Replace from @if to the closing parenthesis
            $replaceStart = $matchStart;
            $replaceLen = $result['endPos'] - $matchStart;
            $replacement = '<?php if (' . $result['content'] . '): ?>';
            
            $str = (string)substr_replace($str, $replacement, $replaceStart, $replaceLen);
            $offset = $replaceStart + strlen($replacement);
        }
        
        $offset = 0;
        while (preg_match('/@elseif\s*\(/s', $str, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $matchStart = (int)$matches[0][1];
            $matchLen = strlen($matches[0][0]);
            $pos = $matchStart + $matchLen;
            $result = $this->extractBalancedParentheses($str, $pos);
            
            // Replace from @elseif to the closing parenthesis
            $replaceStart = $matchStart;
            $replaceLen = $result['endPos'] - $matchStart;
            $replacement = '<?php elseif (' . $result['content'] . '): ?>';
            
            $str = (string)substr_replace($str, $replacement, $replaceStart, $replaceLen);
            $offset = $replaceStart + strlen($replacement);
        }
        $str = preg_replace('/@else\b/', '<?php else: ?>', $str);
        $str = preg_replace('/@endif\b/', '<?php endif; ?>', $str);

        // Parse @isset / @endisset directives
        $str = preg_replace_callback(
            '/@isset\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php if (isset(' . $matches[1] . ')): ?>';
            },
            $str
        );
        $str = preg_replace('/@endisset\b/', '<?php endif; ?>', $str);

        // Parse @empty / @endempty directives
        $str = preg_replace_callback(
            '/@empty\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php if (empty(' . $matches[1] . ')): ?>';
            },
            $str
        );
        $str = preg_replace('/@endempty\b/', '<?php endif; ?>', $str);

        // Parse @foreach / @endforeach directives
        $str = preg_replace_callback(
            '/@foreach\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php foreach (' . $matches[1] . '): ?>';
            },
            $str
        );
        $str = preg_replace('/@endforeach\b/', '<?php endforeach; ?>', $str);

        // Parse @forelse / @empty / @endforelse directives  
        $str = preg_replace_callback(
            '/@forelse\s*\(\s*(.+)\s+as\s+(.+?)\s*\)/',
            function ($matches) {
                return '<?php if (!empty(' . trim($matches[1]) . ')): ?><?php foreach (' . trim($matches[1]) . ' as ' . trim($matches[2]) . '): ?>';
            },
            $str
        );
        // Match @empty only when NOT followed by parentheses (to avoid matching @empty($var))
        $str = preg_replace('/@empty\b(?!\s*\()/', '<?php endforeach; ?><?php else: ?>', $str);
        $str = preg_replace('/@endforelse\b/', '<?php endif; ?>', $str);

        // Parse @for / @endfor directives
        $str = preg_replace_callback(
            '/@for\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php for (' . $matches[1] . '): ?>';
            },
            $str
        );
        $str = preg_replace('/@endfor\b/', '<?php endfor; ?>', $str);

        // Parse @while / @endwhile directives
        $str = preg_replace_callback(
            '/@while\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php while (' . $matches[1] . '): ?>';
            },
            $str
        );
        $str = preg_replace('/@endwhile\b/', '<?php endwhile; ?>', $str);

        // Parse @continue and @break directives
        $str = preg_replace_callback(
            '/@continue(?:\s*\(\s*(\d+)\s*\))?/',
            function ($matches) {
                $level = isset($matches[1]) ? (int)$matches[1] : 1;
                return '<?php continue ' . $level . '; ?>';
            },
            $str
        );
        $str = preg_replace_callback(
            '/@break(?:\s*\(\s*(\d+)\s*\))?/',
            function ($matches) {
                $level = isset($matches[1]) ? (int)$matches[1] : 1;
                return '<?php break ' . $level . '; ?>';
            },
            $str
        );

        // Parse @php / @endphp directives for raw PHP blocks
        $str = preg_replace('/@php\b/', '<?php', $str);
        $str = preg_replace('/@endphp\b/', '?>', $str);

        // Parse @dd directive for dump and die
        $str = preg_replace_callback(
            '/@dd\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php dd(' . $matches[1] . '); ?>';
            },
            $str
        );

        // Parse @dump directive
        $str = preg_replace_callback(
            '/@dump\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?php var_dump(' . $matches[1] . '); ?>';
            },
            $str
        );

        // Parse @json directive
        $str = preg_replace_callback(
            '/@json\s*\(\s*(.+?)\s*\)/',
            function ($matches) {
                return '<?= json_encode(' . $matches[1] . ', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>';
            },
            $str
        );

        // Parse @_e directive for language translation
        $str = preg_replace_callback(
            '/@_e\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]*)[\'"])?\s*\)/',
            function ($matches) {
                $key = addslashes($matches[1]);
                $default = isset($matches[2]) ? addslashes($matches[2]) : $key;
                return '<?= htmlspecialchars($GLOBALS[\'__view\']->trans(\'' . $key . '\', \'' . $default . '\'), ENT_QUOTES, \'UTF-8\'); ?>';
            },
            $str
        );

        // Parse @slot / @endslot directives for default content blocks
        $str = preg_replace_callback(
            '/@slot\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)@endslot/s',
            function ($matches) {
                $section = $matches[1];
                $default = $matches[2];
                // Use base64 encoding to safely pass content through string literal
                $encoded = base64_encode($default);
                return '<?php $GLOBALS[\'__view\']->startSlot(\'' . addslashes($section) . '\'); ?>' . 
                       '<?= base64_decode(\'' . $encoded . '\'); ?>' . 
                       '<?= $GLOBALS[\'__view\']->endSlot(); ?>';
            },
            $str
        );

        // Parse @push / @endpush directives - extract content blocks first
        $str = preg_replace_callback(
            '/@push\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)@endpush/s',
            function ($matches) {
                $stackName = $matches[1];
                $content = $matches[2]; // Don't trim - preserve formatting
                // Use base64 encoding to safely pass content through string literal
                $encoded = base64_encode($content);
                return '<?php $GLOBALS[\'__view\']->pushToStack(\'' . addslashes($stackName) . '\', base64_decode(\'' . $encoded . '\')); ?>';
            },
            $str
        );

        // Parse @stack directive to output pushed content - use placeholder to delay evaluation
        $str = preg_replace_callback(
            '/@stack\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            function ($matches) {
                // Use a unique placeholder that will be replaced after all pushes are executed
                return '___STACK_PLACEHOLDER_' . $matches[1] . '___';
            },
            $str
        );

        

        return $str;
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
     * Start a new section.
     *
     * @param  string  $section
     * @return void
     */
    public function section(string $section, $value = null): void
    {
        $this->currentSection = $section;
        ob_start();
        if ($value !== null) {
            echo $value;

            $this->endSection();
        }
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
        return trim($GLOBALS['__view']->sections[$section] ?? $default);
    }

    /**
     * Set the layout that this view extends.
     *
     * @param  string  $layout
     * @return void
     */
    public function extend(string $layout): void
    {
        $this->extendsLayout = $layout;
    }

    /**
     * Start capturing default content for a slot.
     *
     * @param  string  $section
     * @return void
     */
    public function startSlot(string $section): void
    {
        $this->currentSlot = $section;
        ob_start();
    }

    /**
     * End capturing default content for a slot and return the section content or default.
     *
     * @return string
     */
    public function endSlot(): string
    {
        if ($this->currentSlot === null) {
            throw new SwidlyException('Cannot end slot without starting one.');
        }

        $default = ob_get_clean();
        $section = $this->currentSlot;
        $this->currentSlot = null;
        
        return $this->sections[$section] ?? ($default !== false ? $default : '');
    }

    /**
     * Start pushing content to a stack.
     *
     * @param  string  $stack
     * @return void
     */
    public function startPush(string $stack): void
    {
        $this->currentStack = $stack;
        ob_start();
    }

    /**
     * Stop pushing content to a stack.
     *
     * @return void
     */
    public function stopPush(): void
    {
        if ($this->currentStack === null) {
            throw new SwidlyException('Cannot end push without starting one.');
        }

        $content = ob_get_clean();
        if ($content !== false && $content !== '') {
            $this->pushToStack($this->currentStack, $content);
        }
        $this->currentStack = null;
    }

    /**
     * Push content to a stack directly.
     *
     * @param  string  $stack
     * @param  string  $content
     * @return void
     */
    public function pushToStack(string $stack, string $content): void
    {
        if (!isset($GLOBALS['__view']->stacks[$stack])) {
            $GLOBALS['__view']->stacks[$stack] = [];
        }
        $GLOBALS['__view']->stacks[$stack][] = $content;
    }

    /**
     * Push content to a stack.
     *
     * @param  string  $stack
     * @param  string  $content
     * @return void
     */
    protected function push(string $stack, string $content): void
    {
        $this->pushToStack($stack, $content);
    }

    /**
     * Yield the pushed content for a stack.
     *
     * @param  string  $stack
     * @return string
     */
    public function yieldPushContent(string $stack): string
    {
        if (!isset($GLOBALS['__view']->stacks[$stack])) {
            return '';
        }
        return implode("\n", $GLOBALS['__view']->stacks[$stack]);
    }

    /**
     * Get a translated string by key.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public function trans(string $key, string $default = ''): string
    {
        return $this->data['lang'][$key] ?? $default;
    }

    /**
     * Replace stack placeholders with actual stack content.
     *
     * @param  string  $content
     * @return string
     */
    protected function replaceStackPlaceholders(string $content): string
    {
        return preg_replace_callback(
            '/___STACK_PLACEHOLDER_(.+?)___/',
            function ($matches) {
                return $this->isChild ? $matches[0] : $this->yieldPushContent($matches[1]);
            },
            $content
        );
    }

    protected function replaceExpressionPlaceholders(string $content): string
    {
        return preg_replace_callback(
            '/___EXPRESSION_[^_]+___/',
            function ($matches) {
                return $this->directiveStackPlaceholders[$matches[0]] ?? '';
            },
            $content
        );
    }

    protected function replaceYieldPlaceholders(string $content): string
    {
        // Replace block-form yield placeholders (base64-encoded default content)
        $content = preg_replace_callback(
            '/__VIEW_YIELD_PLACEHOLDER__(.+?)__BLOCK_DEFAULT__(.+?)__/',
            function ($matches) {
                $section = $matches[1];
                $default = base64_decode($matches[2]);
                return $this->yield($section, $default);
            },
            $content
        );

        // Replace inline yield placeholders
        return preg_replace_callback(
            '/__VIEW_YIELD_PLACEHOLDER__(.+?)__DEFAULT__(.*?)__/',
            function ($matches) {
                $section = $matches[1];
                $default = $matches[2];
                return $this->yield($section, $default);
            },
            $content
        );
    }
}