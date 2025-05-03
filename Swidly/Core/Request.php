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

    public function getServer($key, $defaultValue = null) {
        return $this->server[$key] ?? $defaultValue;
    }

    public function getCookie($key, $defaultValue = null) {
        return $_COOKIE[$key] ?? $defaultValue;
    }

    public function getPost($key, $defaultValue = null) {
        return $_POST[$key] ?? $defaultValue;
    }

    public function getGet($key, $defaultValue = null) {
        return $_GET[$key] ?? $defaultValue;
    }

    public function getFiles($key, $defaultValue = null) {
        return $_FILES[$key] ?? $defaultValue;
    }

    public function getSession($key, $defaultValue = null) {
        return $_SESSION[$key] ?? $defaultValue;
    }

    public function getHeader($key, $defaultValue = null) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $defaultValue;
    }

    public function getIp(): string
    {
        return $this->getServer('REMOTE_ADDR');
    }

    public function getHost(): string
    {
        return $this->getServer('HTTP_HOST');
    }

    public function getProtocol(): string
    {
        return $this->getServer('SERVER_PROTOCOL');
    }

    public function getPort(): string
    {
        return $this->getServer('SERVER_PORT');
    }

    public function getMethod(): string
    {
        return $this->getServer('REQUEST_METHOD');
    }

    public function getUri(): string
    {
        return $this->getServer('REQUEST_URI');
    }

    public function getQueryString(): string
    {
        return $this->getServer('QUERY_STRING');
    }

    public function getContentType(): ?string
    {
        return $this->getServer('CONTENT_TYPE');
    }

    public function getAccept(): string
    {
        return $this->getServer('HTTP_ACCEPT');
    }

    public function getAcceptEncoding(): string
    {
        return $this->getServer('HTTP_ACCEPT_ENCODING');
    }

    public function getAcceptLanguage(): string
    {
        return $this->getServer('HTTP_ACCEPT_LANGUAGE');
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

    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest' ||
            str_contains($this->getContentType() ?? '', 'application/json');
    }
}