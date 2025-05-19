<?php

namespace Swidly\Core\Helpers;

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
            $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        // Build the permalink
        $permalink = rtrim($baseUrl, '/') . '/blog/' . $slug;

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
}