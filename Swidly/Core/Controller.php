<?php

namespace Swidly\Core;

use Swidly\Core\Request;

class Controller
{
    public ?object $app = null;
    public mixed $model = null;
    public array $vars = [];

    public function __construct()
    {
        global $app;
        $this->app = $app;
    }

    public function getModel(string $model): Model | bool {
        $themePath = Swidly::theme()['base'];
        $modelDir = glob($themePath.'/models')[0];
        $modelFile = $modelDir.'/'.$model;

        if (file_exists($modelFile.'.php')) {
            require_once $modelFile.'.php';
            $classes = get_declared_classes();
            $modelClass = current(array_filter(
                $classes,
                function($value) use($model) {
                    if (strpos($value, '\\')) {
                        return substr($value, strrpos($value, '\\') + 1, strlen($value)) == $model;
                    } elseif ($value == $model) {
                        return true;
                    }

                    return false;
                }
            ));

            return new $modelClass;
        }

        return false;
    }

    /**
     * @param string $page
     * @param array $data
     * @return void
     * @throws SwidlyException
     */
    public function render(string $page, array $data = []): void
    {
        $base = Swidly::theme()['base'];
        $page = $base.'/views/'.$page.'.php';
        $this->getLanguage();
        extract($data);

        if (!file_exists($page)) {
            throw new SwidlyException('Page doesn\'t exists', 400);
        }

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        ob_start();
        require_once $page;
        $content = ob_get_clean();

        $parsedContent = self::parse($content);

        if(Swidly::isSinglePage() && Swidly::isRequestJson()) {
            $response = new Response();
            $response->addData('content', $parsedContent);

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
    protected function parse(string $str = null): array|string|null
    {
        $vars = $this->vars;
        $pattern = '/{([a-zA-Z0-9_:]+)(?:,\s*default=([a-zA-Z0-9_:]+))?}/';

        return preg_replace_callback($pattern, function ($matches) use ($vars) {
            $word = $vars[$matches[1]] ?? ($vars['lang'][$matches[1]] ?? $matches[2] ?? null);

            return isset($word) ? $this->parse($word) : '';
        }, $str);
    }

    protected function getLanguage(): void
    {
        $default_lang = (new Request())->get('lang', Swidly::getConfig('default_lang'));
        $lang_path = __DIR__."/../lang/{$default_lang}.json";

        if (file_exists($lang_path)) {
            $string = file_get_contents($lang_path);
            $this->lang = json_decode($string, true);
        } else {
            echo 'unable to find lang file';
        }
    }


    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function __get($key)
    {
        return $this->vars[$key];
    }
}