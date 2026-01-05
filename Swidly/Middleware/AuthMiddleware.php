<?php

declare(strict_types=1);

namespace Swidly\Middleware;

use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Core\Model;

/**
 * AuthMiddleware
 * 
 * Handles authentication checks and redirects for protected routes.
 * Validates session state, user existence, and manages login/logout flows.
 */
class AuthMiddleware extends BaseMiddleWare 
{
    /**
     * Routes that are public and don't require authentication
     */
    private const PUBLIC_ROUTES = [
        'auth/login',
        'auth/register',
        'auth/forgot-password',
        'auth/reset-password',
    ];

    /**
     * Routes that should redirect authenticated users away
     */
    private const GUEST_ONLY_ROUTES = [
        'auth/login',
        'auth/register',
        'auth/forgot-password',
        'auth/reset-password',
    ];

    /**
     * Default redirect after login
     */
    private const DEFAULT_REDIRECT = '/dashboard';

    /**
     * Execute authentication middleware logic
     * 
     * @param mixed $request Request object
     * @param mixed $response Response object
     * @throws SwidlyException
     */
    public function execute($request, $response): void
    {
        $this->initializeSession();
        
        $isAuthenticated = $this->isUserAuthenticated();
        $currentPath = $this->getCurrentPath($request);
        $isPublicRoute = $this->isPublicRoute($currentPath);
        $isGuestOnlyRoute = $this->isGuestOnlyRoute($currentPath);

        // Handle unauthenticated users
        if (!$isAuthenticated) {
            $this->handleUnauthenticatedUser($request, $response, $currentPath, $isPublicRoute);
            return;
        }

        // Handle authenticated users accessing guest-only routes
        if ($isGuestOnlyRoute) {
            $this->redirectAuthenticatedUser($response);
            return;
        }

        // Validate session integrity for authenticated users
        $this->validateSessionIntegrity($response);
    }

    /**
     * Ensure session is started
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    private function isUserAuthenticated(): bool
    {
        $sessionName = Swidly::getConfig('session_name');
        return isset($_SESSION[$sessionName]) && !empty($_SESSION[$sessionName]);
    }

    /**
     * Get current request path
     * 
     * @param mixed $request
     * @return string
     */
    private function getCurrentPath($request): string
    {
        $path = $request->get('path') ?? '';
        return trim($path, '/');
    }

    /**
     * Check if route is public
     * 
     * @param string $path
     * @return bool
     */
    private function isPublicRoute(string $path): bool
    {
        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($this->pathMatches($path, $publicRoute)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if route is guest-only
     * 
     * @param string $path
     * @return bool
     */
    private function isGuestOnlyRoute(string $path): bool
    {
        foreach (self::GUEST_ONLY_ROUTES as $guestRoute) {
            if ($this->pathMatches($path, $guestRoute)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if path matches pattern (supports wildcards)
     * 
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    private function pathMatches(string $path, string $pattern): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Wildcard support (e.g., "auth/*")
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
            return (bool) preg_match($regex, $path);
        }

        return false;
    }

    /**
     * Handle unauthenticated user access
     * 
     * @param mixed $request
     * @param mixed $response
     * @param string $currentPath
     * @param bool $isPublicRoute
     * @throws SwidlyException
     */
    private function handleUnauthenticatedUser($request, $response, string $currentPath, bool $isPublicRoute): void
    {
        // Allow access to public routes
        if ($isPublicRoute) {
            return;
        }

        // Save intended destination for post-login redirect
        $this->saveIntendedUrl($request);

        // Redirect to login
        $response->redirect('/auth/login');
        exit;
    }

    /**
     * Save the URL user was trying to access for post-login redirect
     * 
     * @param mixed $request
     */
    private function saveIntendedUrl($request): void
    {
        $requestUri = $request->getRequestUri();
        
        // Don't save if already on login/register pages
        if (!str_contains($requestUri, '/auth/')) {
            $_SESSION['redirect_after_login'] = $requestUri;
        }
    }

    /**
     * Redirect authenticated users away from guest-only routes
     * 
     * @param mixed $response
     */
    private function redirectAuthenticatedUser($response): void
    {
        // Check if there's a saved redirect URL
        $redirectUrl = $_SESSION['redirect_after_login'] ?? self::DEFAULT_REDIRECT;
        
        // Clear the saved redirect
        unset($_SESSION['redirect_after_login']);

        $response->redirect($redirectUrl);
        exit;
    }

    /**
     * Validate session integrity and user existence
     * 
     * @param mixed $response
     */
    private function validateSessionIntegrity($response): void
    {
        $sessionName = Swidly::getConfig('session_name');
        $userId = $_SESSION[$sessionName] ?? null;

        if (!$userId) {
            $this->clearSessionAndRedirect($response);
            return;
        }

        // Verify user still exists in database
        try {
            /** @var \Swidly\themes\localgem\models\UserModel $userModel */
            $userModel = Model::load('UserModel');
            $user = $userModel->findById((int)$userId);

            if (!$user) {
                $this->clearSessionAndRedirect($response, 'Your account no longer exists.');
                return;
            }

            // Optional: Check if account is active/not banned
            // if (method_exists($user, 'isActive') && !$user->isActive()) {
            //     $this->clearSessionAndRedirect($response, 'Your account has been deactivated.');
            //     return;
            // }

            // Refresh session activity timestamp (prevent session fixation)
            $this->refreshSessionActivity();

        } catch (\Exception $e) {
            // Database error - fail gracefully
            error_log('AuthMiddleware: Failed to validate user session: ' . $e->getMessage());
            // Don't redirect on DB errors, allow the request to proceed
        }
    }

    /**
     * Clear session and redirect to login
     * 
     * @param mixed $response
     * @param string|null $message Optional error message
     */
    private function clearSessionAndRedirect($response, ?string $message = null): void
    {
        $sessionName = Swidly::getConfig('session_name');
        unset($_SESSION[$sessionName]);

        if ($message) {
            $_SESSION['auth_error'] = $message;
        }

        $response->redirect('/auth/login');
        exit;
    }

    /**
     * Refresh session activity timestamp
     * Helps prevent session fixation and tracks user activity
     */
    private function refreshSessionActivity(): void
    {
        $_SESSION['last_activity'] = time();

        // Optional: Session timeout check
        // $timeout = Swidly::getConfig('session_timeout') ?? 3600; // 1 hour default
        // if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        //     $this->clearSessionAndRedirect($response, 'Your session has expired. Please log in again.');
        // }
    }

    /**
     * Get authenticated user ID
     * 
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        $sessionName = Swidly::getConfig('session_name');
        return isset($_SESSION[$sessionName]) ? (int)$_SESSION[$sessionName] : null;
    }

    /**
     * Check if current user is authenticated (static helper)
     * 
     * @return bool
     */
    public static function check(): bool
    {
        $sessionName = Swidly::getConfig('session_name');
        return isset($_SESSION[$sessionName]) && !empty($_SESSION[$sessionName]);
    }

    /**
     * Get authenticated user model (static helper)
     * 
     * @return mixed|null
     */
    public static function user()
    {
        $userId = self::getUserId();
        if (!$userId) {
            return null;
        }

        try {
            /** @var \Swidly\themes\localgem\models\UserModel $userModel */
            $userModel = Model::load('UserModel');
            return $userModel->findById($userId);
        } catch (\Exception $e) {
            error_log('AuthMiddleware::user() failed: ' . $e->getMessage());
            return null;
        }
    }
}
