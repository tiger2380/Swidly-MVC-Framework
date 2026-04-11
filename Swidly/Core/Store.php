<?php

namespace Swidly\Core;

/**
 * The Store class handles session management, including saving, retrieving,
 * and deleting session variables, as well as CSRF token management
 * and flash messages.
 *
 * @author thadd
 */
class Store
{
    /**
     * Ensures the session has been started.
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Starts a session if not already started.
     */
    public static function start(): void
    {
        self::ensureSessionStarted();
    }

    /**
     * Save a value to the session.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function save(string $key, mixed $value): void
    {
        self::ensureSessionStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a session value or return a default if key does not exist.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureSessionStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function hasKey(string $key): bool
    {
        self::ensureSessionStarted();
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Delete a session value by key.
     *
     * @param string $key
     */
    public static function delete(string $key): void
    {
        self::ensureSessionStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Generate or retrieve the CSRF token.
     *
     * @return string
     * @throws \Exception
     */
    public static function csrf(): string
    {
        self::ensureSessionStarted();

        if (!self::hasKey('_csrf_token')) {
            self::save('_csrf_token', bin2hex(random_bytes(32)));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Verify the given CSRF token matches the stored one.
     *
     * @param string $token
     * @return bool
     */
    public static function verifyCsrf(string $token): bool
    {
        self::ensureSessionStarted();
        return hash_equals(self::get('_csrf_token', ''), $token);
    }

    /**
     * Regenerate the CSRF token.
     *
     * @throws \Exception
     */
    public static function regenerateCsrf(): void
    {
        self::save('_csrf_token', bin2hex(random_bytes(32)));
    }

    /**
     * Delete the CSRF token from the session.
     */
    public static function deleteCsrf(): void
    {
        self::delete('_csrf_token');
    }

    /**
     * Generate a simple math captcha and store the answer in the session.
     */
    public static function generateCaptcha(): void
    {
        $a = random_int(1, 20);
        $b = random_int(1, 20);
        $answer = $a + $b;
        $secret = self::getCaptchaSecret();
        $hash = hash_hmac('sha256', (string) $answer, $secret);

        self::save('_captcha_question', "What is {$a} + {$b}?");
        self::save('_captcha_answer', $answer);
        self::save('_captcha_hash', $hash);
    }

    /**
     * Get or create a stable captcha HMAC secret (not tied to the CSRF token).
     *
     * @return string
     */
    private static function getCaptchaSecret(): string
    {
        self::ensureSessionStarted();
        if (!self::hasKey('_captcha_secret')) {
            self::save('_captcha_secret', bin2hex(random_bytes(32)));
        }
        return self::get('_captcha_secret');
    }

    /**
     * Verify the submitted captcha answer against the session hash.
     *
     * @param  int|string  $answer  The user-submitted answer
     * @param  string      $hash    The hash submitted with the form
     * @return bool
     */
    public static function verifyCaptcha(int|string $answer, string $hash): bool
    {
        self::ensureSessionStarted();
        $secret = self::getCaptchaSecret();
        $expected = hash_hmac('sha256', (string) (int) $answer, $secret);
        $valid = hash_equals($expected, $hash);

        // Clear captcha after verification attempt
        self::delete('_captcha_question');
        self::delete('_captcha_answer');
        self::delete('_captcha_hash');

        return $valid;
    }

    /**
     * Copy a session value to a new key.
     *
     * @param string $key
     * @param string|null $newKey
     */
    public static function copy(string $key, ?string $newKey): void
    {
        if (self::hasKey($key) && $newKey) {
            self::save($newKey, $_SESSION[$key]);
        }
    }

    /**
     * Create and display a flash message.
     *
     * @param string $key
     * @param string $type
     * @param array $attributes
     * @return string|null
     */
    public static function flashMessage(string $key, string $type = 'info', array $attributes = []): ?string
    {
        if (!self::hasKey($key)) {
            return null;
        }

        $message = $_SESSION[$key];
        $attrs = self::buildAttributes($attributes);

        self::delete($key);

        return sprintf(
            '<div class="alert alert-%s mb-2 alert-dismissible fade show" role="alert" %s>%s</div>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            $attrs,
            $message
        );
    }

    /**
     * Helper method to build HTML attributes.
     *
     * @param array $attributes
     * @return string
     */
    private static function buildAttributes(array $attributes): string
    {
        $built = '';

        foreach ($attributes as $key => $value) {
            $built .= sprintf(' %s="%s"', htmlspecialchars($key, ENT_QUOTES, 'UTF-8'), htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        return $built;
    }
}