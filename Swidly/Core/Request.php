<?php

declare(strict_types=1);

namespace Swidly\Core;

class Request
{
    protected array $request = array();
    protected ?array $server = null;
    protected ?object $user = null;
    protected ?array $decoded = null;
    public array $vars = array();
    /**
     * @var mixed|void
     */
    private bool $is_authenticated = false;
    private DB $db;
    private const MAX_CONTENT_LENGTH = 10485760; // 10MB
    private array $trustedProxies = ['127.0.0.1', '::1'];
    private array $trustedHosts = [];

    public function __construct()
    {
        $this->validateContentLength();
        $this->validateHost();
        
        $content = $this->getRequestContent();
        $decoded = $this->parseJsonContent($content);

        if(!is_array($decoded)) {
            $decoded = [];
        }

        $this->decoded = $decoded;
        $this->request = $this->sanitizeInput(array_merge($_GET, $_POST, $decoded));
        $this->server = $this->filterServerVars($_SERVER);
        $this->db = DB::create();
        $this->CheckAuthentication();
    }

    private function validateContentLength(): void
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > self::MAX_CONTENT_LENGTH) {
            throw new SwidlyException('Content length exceeds maximum allowed size');
        }
    }

    private function validateHost(): void
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($this->trustedHosts) && !in_array($host, $this->trustedHosts, true)) {
            throw new SwidlyException('Invalid host');
        }
    }

    private function getRequestContent(): string
    {
        $content = file_get_contents("php://input");
        if ($content === false) {
            throw new SwidlyException('Failed to read request content');
        }
        return trim($content);
    }

    public function getRequestUri(): string
    {
        return $this->getServer('REQUEST_URI', '/');
    }

    private function parseJsonContent(string $content): ?array
    {
        if (empty($content)) {
            return [];
        }

        $contentType = $this->getServer('CONTENT_TYPE', '');
    
        // Handle URL-encoded form data
        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $decoded = [];
            parse_str($content, $decoded);
            return $decoded;
        }
        
        // Handle JSON data
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SwidlyException('Invalid JSON content: ' . json_last_error_msg());
            }
            return $decoded;
        }

        return [];
    }

    private function sanitizeInput(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Remove NULL bytes and strip HTML tags
                $value = str_replace(chr(0), '', strip_tags($value));
            } elseif (is_array($value)) {
                $value = $this->sanitizeInput($value);
            }
            $sanitized[$key] = $value;
        }
        return $sanitized;
    }

    private function filterServerVars(array $server): array
    {
        $filtered = [];
        foreach ($server as $key => $value) {
            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $filtered[$key] = $value;
        }
        return $filtered;
    }

    public function set($key, $value) {
        $this->request[$key] = $value;
    }

    public function get(string $key, $filter = null, $options = null, $defaultValue = null) {
        $value = $this->request[$key] ?? $defaultValue;

        if (is_array($value)) {
            if ($filter !== null) {
                return filter_var_array($value, $filter, $options ?? []);
            }
            return array_map(fn($v) => is_string($v) ? htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v, $value);
        }

        if ($filter !== null) {
            return filter_var($value, $filter, $options ?? []);
        }

        return is_string($value) ? htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $value;
    }

    public function getServer($key, $defaultValue = null) {
        return $this->server[$key] ?? $defaultValue;
    }

    public function getCookie($key, $defaultValue = null) {
        return $_COOKIE[$key] ?? $defaultValue;
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
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }

        if (!in_array($ip, $this->trustedProxies, true)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
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
            $user = $this->db->table(Swidly::getConfig('user::table'))->Select()->where([Swidly::getConfig('user::auth_field') => Store::get(Swidly::getConfig('session_name'))]);
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

    public function getBody(): array
    {
        $body = [];

        if($this->isPost()) {
            $body = filter_var_array(array_merge($_POST, $this->decoded ?? []), FILTER_UNSAFE_RAW);
        }

        if($this->isGet()) {
            $body = filter_var_array(array_merge($_GET, $this->decoded ?? []), FILTER_UNSAFE_RAW);
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

    public function validateRequestMethod(): void
    {
        $method = $this->getMethod();
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        
        if (!in_array($method, $allowedMethods, true)) {
            throw new SwidlyException('Invalid request method');
        }
    }

    public function isSecure(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        return false;
    }

    public function setTrustedProxies(array $proxies): void
    {
        $this->trustedProxies = array_map('strval', $proxies);
    }

    public function setTrustedHosts(array $hosts): void
    {
        $this->trustedHosts = array_map('strval', $hosts);
    }
}
