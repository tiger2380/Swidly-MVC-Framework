<?php

declare(strict_types=1);

namespace Swidly\Core;

class Request
{
    protected array $request = array();
    protected ?array $server = null;
    protected ?object $user = null;
    public array $vars = array();
    /**
     * @var mixed|void
     */
    private bool $is_authenticated = false;

    public function __construct()
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if(!is_array($decoded)) {
            $decoded = [];
        }

        $this->request = array_merge($_GET, $_POST, $_COOKIE, $_FILES, $decoded, $_SERVER, $_SESSION);
        $this->server = $_SERVER;
        $this->CheckAuthentication();
    }

    public function set($key, $value) {
        $this->request[$key] = $value;
    }

    public function get($key, $defaultValue = null) {
        return $this->request[$key] ?? $defaultValue;
    }

    protected function getUser(): ?object
    {
        $response = null;
        if($this->is_authenticated) {
            $user = DB::Table(Swidly::getConfig('user::table'))->Select()->WhereOnce([Swidly::getConfig('user::auth_field') => Store::get(Swidly::getConfig('session_name'))]);
            $response = (object)$user;
        }

        return $response;
    }

    public function getUri()
    {
        return trim($this->server['REQUEST_URI'], '/');
    }

    public function getType()
    {
        return $this->server['REQUEST_METHOD'];
    }

    public function isPost(): bool
    {
        return $this->getType() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getType() === 'GET';
    }

    public function CheckAuthentication(): void
    {
        $this->vars['is_authenticated'] = isset($_SESSION[Swidly::getConfig('session_name')]);
    }

    public function __get($key) {
        if(isset($this->vars[$key])) {
            return $this->vars[$key];
        }
    }

    public function __set($key, $value) {
        $this->vars[$key] = $value;
    }

    public function getBody(): array
    {
        $body = [];

        if($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key);
            }
        }

        if($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key);
            }
        }

        return $body;
    }

    public function serverName() {
        return $this->server['SCRIPT_NAME'];
    }
}