<?php

namespace Swidly\Core\Helpers;

use Swidly\Core\Swidly;

class UrlHelper
{
    /**
     * Generate a permalink for a blog post
     * @param string $baseUrl Base URL of the site
     * @param string $slug Post slug
     * @param array $params Optional URL parameters
     * @return string
     */
    public static function getPermalink(string $slug, array $params = [], string $baseUrl = ''): string 
    {
        // Get base URL from config if not provided
        if (empty($baseUrl)) {
            $baseUrl = Swidly::getConfig('app::base_url') ?: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        // Build the permalink
        $permalink = rtrim($baseUrl, '/') . '/' . $slug;

        // Add any additional parameters
        if (!empty($params)) {
            $permalink .= '?' . http_build_query($params);
        }

        return $permalink;
    }

    /**
     * Get the current URL
     */
    public static function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Add or update query parameters to URL
     */
    public static function addQueryParams(string $url, array $params): string
    {
        $parsedUrl = parse_url($url);
        $query = [];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        
        $query = array_merge($query, $params);
        $updatedQuery = http_build_query($query);
        
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        
        return $scheme . $host . $path . ($updatedQuery ? '?' . $updatedQuery : '');
    }

    /**
     * Remove query parameters from URL
     */
    public static function removeQueryParams(string $url, array $paramsToRemove): string
    {
        $parsedUrl = parse_url($url);
        $query = [];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
            foreach ($paramsToRemove as $param) {
                unset($query[$param]);
            }
        }
        
        $updatedQuery = http_build_query($query);
        
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        
        return $scheme . $host . $path . ($updatedQuery ? '?' . $updatedQuery : '');
    }

    /**
     * Create a pagination URL
     */
    public static function getPaginationUrl(int $page, string $baseUrl = ''): string
    {
        return static::addQueryParams($baseUrl ?: static::getCurrentUrl(), ['page' => $page]);
    }

    /**
     * Generate category URL
     */
    public static function getCategoryUrl(string $category, string $baseUrl = ''): string
    {
        if (empty($baseUrl)) {
            $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }
        return rtrim($baseUrl, '/') . '/category/' . urlencode($category);
    }

    /**
     * Check if current URL matches pattern
     */
    public static function isCurrentUrl(string $pattern): bool
    {
        return (bool) preg_match($pattern, static::getCurrentUrl());
    }

    /**
     * Get clean path without query string
     */
    public static function getCleanPath(string $url): string
    {
        return strtok($url, '?');
    }

    public static function slugify(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace spaces and special characters with hyphens
        $text = preg_replace('/[^\w]+/', '-', $text);
        
        // Trim hyphens from start and end
        return trim($text, '-');
    }
    /**
     * Generate a full URL for a given path
     */
    public static function fullUrl(string $path, string $baseUrl = ''): string
    {
        // Get base URL from config if not provided
        if (empty($baseUrl)) {
            $baseUrl = Swidly::getConfig('app::base_url') ?: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        // Ensure base URL ends with a slash
        $baseUrl = rtrim($baseUrl, '/') . '/';

        // Return the full URL
        return $baseUrl . ltrim($path, '/');
    }
    /**
     * Generate a URL for a specific route
     */
    public static function route(string $name, array $params = [], string $baseUrl = ''): string
    {
        // Get base URL from config if not provided
        if (empty($baseUrl)) {
            $baseUrl = Swidly::getConfig('app::base_url') ?: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        // Ensure base URL ends with a slash
        $baseUrl = rtrim($baseUrl, '/') . '/';

        // Generate the route URL
        $routePath = Swidly::getRoutePath($name);
        if (!$routePath) {
            throw new \InvalidArgumentException("Route '$name' not found");
        }

        // Add parameters to the route path
        if (!empty($params)) {
            $routePath .= '?' . http_build_query($params);
        }

        return $baseUrl . ltrim($routePath, '/');
    }
    /**
     * Generate a URL for a specific controller action
     */
    public static function action(string $controller, string $action, array $params = [], string $baseUrl = ''): string
    {
        // Get base URL from config if not provided
        if (empty($baseUrl)) {
            $baseUrl = Swidly::getConfig('app::base_url') ?: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        // Ensure base URL ends with a slash
        $baseUrl = rtrim($baseUrl, '/') . '/';

        // Generate the action URL
        $actionPath = Swidly::getActionPath($controller, $action);
        if (!$actionPath) {
            throw new \InvalidArgumentException("Action '$controller@$action' not found");
        }

        // Add parameters to the action path
        if (!empty($params)) {
            $actionPath .= '?' . http_build_query($params);
        }

        return $baseUrl . ltrim($actionPath, '/');
    }

    /**
     * Parse links in a string and convert them to HTML links
     * @param string $text Text containing URLs to parse
     * @param array $attributes Optional HTML attributes for the links
     * @return string Text with parsed links
     */
    public static function parseLinks(string $text, array $attributes = []): string
    {
        // URL pattern matching both http(s) and non-protocol URLs
        $pattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        
        // Build HTML attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }

        return preg_replace_callback($pattern, function($matches) use ($attrs) {
            $url = $matches[0];
            
            // Add http:// if protocol is missing
            if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                $url = "http://" . $url;
            }
            
            return sprintf('<a href="%s"%s>%s</a>', 
                htmlspecialchars($url),
                $attrs,
                htmlspecialchars($matches[0])
            );
        }, $text);
    }
}