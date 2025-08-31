<?php

namespace Swidly\Core;

use Swidly\Core\Request;

class Controller
{
    protected ?object $app = null;
    protected mixed $model = null;
    protected array $vars = [];
    protected array $safeIncludePaths = [];

    /**
     * Initialize controller with application instance
     * @throws \RuntimeException If app instance is not available
     */
    public function __construct()
    {
        $this->safeIncludePaths = [Swidly::theme()['base'] . '/views/'];
    }

    /**
     * Get a model instance from the current theme
     *
     * @param string $model Name of the model class to load
     * @return ?Model Returns a Model instance or null if not found
     * @throws \RuntimeException If there's an error loading the model
     */
    public function getModel(string $model): ?Model
    {
        if (empty($model)) {
            return null;
        }

        try {
            $themePath = Swidly::theme()['base'] ?? '';

            // Check if theme path exists
            if (empty($themePath) || !is_dir($themePath)) {
                throw new \RuntimeException("Invalid theme path");
            }

            // Find models directory
            $modelDirMatches = glob($themePath . '/models');

            if (empty($modelDirMatches) || !is_dir($modelDirMatches[0])) {
                throw new \RuntimeException("Models directory not found");
            }

            $modelDir = $modelDirMatches[0];
            $modelFile = $modelDir . '/' . $model . '.php';

            // Check if model file exists
            if (!file_exists($modelFile)) {
                return null;
            }

            // Get classes before including the file
            $beforeClasses = get_declared_classes();

            // Include the model file
            require_once $modelFile;

            // Get classes after including the file
            $afterClasses = get_declared_classes();
            $newClasses = array_diff($afterClasses, $beforeClasses);

            // Find the model class
            $modelClass = null;
            foreach ($newClasses as $class) {
                $className = (strpos($class, '\\') !== false)
                    ? substr($class, strrpos($class, '\\') + 1)
                    : $class;

                if ($className === $model) {
                    $modelClass = $class;
                    break;
                }
            }

            // If model class not found, try to find it in all declared classes
            if ($modelClass === null) {
                $modelClass = current(array_filter(
                    $afterClasses,
                    function($value) use($model) {
                        if (strpos($value, '\\') !== false) {
                            return substr($value, strrpos($value, '\\') + 1) === $model;
                        }

                        return $value === $model;
                    }
                )) ?: null;
            }

            // If model class is found, instantiate and return it
            if ($modelClass !== null) {
                $instance = new $modelClass();

                // Verify that the instance is a Model
                if ($instance instanceof Model) {
                    return $instance;
                }

                throw new \RuntimeException("Class '$modelClass' is not a Model instance");
            }

            return null;
        } catch (\Exception $e) {
            // Log the error, you might want to implement proper logging here
            error_log("Error loading model '$model': " . $e->getMessage());

            // Re-throw as RuntimeException
            throw new \RuntimeException("Failed to load model '$model': " . $e->getMessage(), 0, $e);
        }

        return null;
    }

    /**
     * @param string $page
     * @param array $data
     * @return void
     * @throws SwidlyException
     */
    /**
     * Render a view page with data
     * @param string $page Page name without .php extension
     * @param array $data Data to pass to the view
     * @throws SwidlyException If page doesn't exist or path traversal is detected
     */
    public function render(string $page, array $data = []): void
    {
        // Prevent path traversal attacks
        if (preg_match('/\.\.\/|\.\.\\\\/', $page)) {
            throw new SwidlyException('Invalid page path', 400);
        }

        // Sanitize and set default section image
        $data['data']['sectionImage'] = filter_var(
            $data['data']['sectionImage'] ?? '/assets/img/g3c2b47d6be5c231d90519a1694741953bae508ba3634dc8ed55dd7a417eb241565c55cc8498553b629174efd8c63ee15a371e6cc1b9df0dfb679b18935f8171f_640.jpg',
            FILTER_SANITIZE_URL
        );
        
        $base = Swidly::theme()['base'];
        if (!$base || !is_dir($base)) {
            throw new SwidlyException('Invalid theme base path', 500);
        }

        $page = $base . '/views/' . ltrim($page, '/') . '.php';
        $this->getLanguage();

        if (!file_exists($page)) {
            throw new SwidlyException('Page doesn\'t exists', 400);
        }

        extract($data);
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        ob_start();
        require_once $page;
        $content = ob_get_clean();

        $parsedContent = self::parseIncludes(self::parse($content));

        //if(Swidly::isSinglePage() && Swidly::isRequestJson()) {
        if(Swidly::isRequestJson()) {
            $response = new Response();
            $response->addData('content', $parsedContent);
            $response->addData('data', $data);

            if(array_key_exists('title', $data)) {
                $response->addData('title', $data['title']);
            }

            $response->json();
        } else  {
            if(!Swidly::isSinglePage()) {
                echo $parsedContent;
            }
        }
    }

    /**
     * @param string|null $str
     * @return array|string|null
     */
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

        $vars = $this->vars;
        $pattern = '/{([a-zA-Z0-9_:]+)(?:,\s*default=([a-zA-Z0-9_:]+))?}/';

        $result = preg_replace_callback($pattern, function ($matches) use ($vars) {
            $key = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            $default = isset($matches[2]) ? htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') : null;
            
            $word = $vars[$key] ?? ($vars['lang'][$key] ?? $default ?? null);
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

        $this->vars['lang'] = $lang_data;
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

                return $this->parse($content);
            }

            return '';
        }, $str);
    }

    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function __get($key)
    {
        return $this->vars[$key] ?? null;
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
}