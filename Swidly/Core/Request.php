<?php

declare(strict_types=1);

namespace Swidly\Core;

use Swidly\themes\localgem\models\UserModel;

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
    private array $validationErrors = [];
    private static ?Request $instance = null;

    public function __construct()
    {
        $this->validateContentLength();
        $this->validateHost();
        $this->server = $this->filterServerVars($_SERVER);
        
        $content = $this->getRequestContent();
        $decoded = $this->parseJsonContent($content);

        if(!is_array($decoded)) {
            $decoded = [];
        }

        $this->decoded = $decoded;
        
        $this->request = $this->sanitizeInput(array_merge($_GET, $_POST, $decoded));
        $this->db = DB::getInstance();
        $this->CheckAuthentication();
        self::$instance = $this;
    }

    public static function getInstance(): ?Request
    {
        return self::$instance;
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

    /**
     * Get authenticated user model
     * 
     * @return UserModel|null
     */
    public function getUser(): ?Model
    {
        $response = null;
        if($this->isAuthenticated()) {
            // Load UserModel from theme
            /** @var UserModel $userModel */
            $userModel = Model::load('UserModel');
            if ($userModel) {
                $userId = Store::get(Swidly::getConfig('session_name'));
                $response = $userModel->find(['id' => $userId]);
            }
        }

        return $response;
    }

    public function isAuthenticated(): bool
    {
        return Store::hasKey(Swidly::getConfig('session_name')) && !empty(Store::get(Swidly::getConfig('session_name')));
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

    /**
     * Validate request data against rules
     * 
     * @param array $rules Validation rules in format ['field' => 'rule1|rule2:param']
     * @param array|null $data Optional data to validate (defaults to request data)
     * @return bool True if validation passes
     */
    public function validate(array $rules, ?array $data = null): bool
    {
        $this->validationErrors = [];
        $data = $data ?? $this->request;

        foreach ($rules as $field => $ruleString) {
            $fieldRules = is_string($ruleString) ? explode('|', $ruleString) : [$ruleString];
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->validationErrors);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Apply a single validation rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule string (e.g., 'required', 'max:100')
     * @return void
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        // Skip other rules if field is not required and is empty
        if ($ruleName !== 'required' && ($value === null || $value === '')) {
            return;
        }

        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "The {$field} must be numeric.");
                }
                break;

            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;

            case 'alpha':
                if (!preg_match('/^[a-zA-Z]+$/', $value)) {
                    $this->addError($field, "The {$field} must contain only letters.");
                }
                break;

            case 'alphanumeric':
                if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    $this->addError($field, "The {$field} must contain only letters and numbers.");
                }
                break;

            case 'alpha_dash':
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    $this->addError($field, "The {$field} must contain only letters, numbers, dashes, and underscores.");
                }
                break;

            case 'min':
                if ($parameter === null) break;
                $length = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? $value : 0);
                if ($length < (int)$parameter) {
                    $this->addError($field, "The {$field} must be at least {$parameter} characters.");
                }
                break;

            case 'max':
                if ($parameter === null) break;
                $length = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? $value : 0);
                if ($length > (int)$parameter) {
                    $this->addError($field, "The {$field} must not exceed {$parameter} characters.");
                }
                break;

            case 'between':
                if ($parameter === null) break;
                $params = explode(',', $parameter);
                if (count($params) !== 2) break;
                $length = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? $value : 0);
                if ($length < (int)$params[0] || $length > (int)$params[1]) {
                    $this->addError($field, "The {$field} must be between {$params[0]} and {$params[1]} characters.");
                }
                break;

            case 'in':
                if ($parameter === null) break;
                $allowed = explode(',', $parameter);
                if (!in_array($value, $allowed, true)) {
                    $this->addError($field, "The selected {$field} is invalid.");
                }
                break;

            case 'regex':
                if ($parameter === null) break;
                if (!preg_match($parameter, $value)) {
                    $this->addError($field, "The {$field} format is invalid.");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->request[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    $this->addError($field, "The {$field} confirmation does not match.");
                }
                break;

            case 'same':
                if ($parameter === null) break;
                $otherValue = $this->request[$parameter] ?? null;
                if ($value !== $otherValue) {
                    $this->addError($field, "The {$field} must match {$parameter}.");
                }
                break;

            case 'different':
                if ($parameter === null) break;
                $otherValue = $this->request[$parameter] ?? null;
                if ($value === $otherValue) {
                    $this->addError($field, "The {$field} must be different from {$parameter}.");
                }
                break;

            case 'date':
                if (strtotime($value) === false) {
                    $this->addError($field, "The {$field} must be a valid date.");
                }
                break;

            case 'before':
                if ($parameter === null) break;
                $date = strtotime($value);
                $beforeDate = strtotime($parameter);
                if ($date === false || $beforeDate === false || $date >= $beforeDate) {
                    $this->addError($field, "The {$field} must be a date before {$parameter}.");
                }
                break;

            case 'after':
                if ($parameter === null) break;
                $date = strtotime($value);
                $afterDate = strtotime($parameter);
                if ($date === false || $afterDate === false || $date <= $afterDate) {
                    $this->addError($field, "The {$field} must be a date after {$parameter}.");
                }
                break;

            case 'boolean':
                if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->addError($field, "The {$field} must be a boolean.");
                }
                break;

            case 'array':
                if (!is_array($value)) {
                    $this->addError($field, "The {$field} must be an array.");
                }
                break;

            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, "The {$field} must be a string.");
                }
                break;
        }
    }

    /**
     * Add a validation error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }
        $this->validationErrors[$field][] = $message;
    }

    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get validation errors for a specific field
     * 
     * @param string $field Field name
     * @return array
     */
    public function error(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }

    /**
     * Get the first validation error for a specific field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function firstError(string $field): ?string
    {
        return $this->validationErrors[$field][0] ?? null;
    }

    /**
     * Check if validation has errors
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Check if a specific field has errors
     * 
     * @param string $field Field name
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->validationErrors[$field]) && !empty($this->validationErrors[$field]);
    }

    /**
     * Flash input data to session for retrieval after redirect
     * 
     * @param array|null $input Optional input data (defaults to all request data)
     * @return void
     */
    public function flashInput(?array $input = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_old_input'] = $input ?? $this->request;
    }

    /**
     * Flash validation errors to session
     * 
     * @return void
     */
    public function flashErrors(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_errors'] = $this->validationErrors;
    }

    /**
     * Flash both input and errors to session
     * 
     * @param array|null $input Optional input data (defaults to all request data)
     * @return void
     */
    public function flash(?array $input = null): void
    {
        $this->flashInput($input);
        $this->flashErrors();
    }

    /**
     * Get old input value from previous request
     * 
     * @param string|null $key Field name (null to get all old input)
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function old(?string $key = null, mixed $default = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $oldInput = $_SESSION['_old_input'] ?? [];

        if ($key === null) {
            return $oldInput;
        }

        return $oldInput[$key] ?? $default;
    }

    /**
     * Get flashed errors from session
     * 
     * @return array
     */
    public function getFlashedErrors(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['_errors'] ?? [];
    }

    /**
     * Clear old input and errors from session
     * 
     * @return void
     */
    public function clearFlashed(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
    }

    /**
     * Validate and flash on failure
     * 
     * @param array $rules Validation rules
     * @param array|null $data Optional data to validate
     * @return bool True if validation passes
     */
    public function validateAndFlash(array $rules, ?array $data = null): bool
    {
        $isValid = $this->validate($rules, $data);
        
        if (!$isValid) {
            $this->flash($data);
        }
        
        return $isValid;
    }
}
