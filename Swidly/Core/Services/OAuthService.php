<?php

declare(strict_types=1);

namespace Swidly\Core\Services;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\GenericProvider;
use Swidly\Core\Swidly;

/**
 * OAuth Service
 * Handles OAuth authentication for Google, Facebook, and X (Twitter)
 */
class OAuthService
{
    public function __construct()
    {
    }

    /**
     * Get OAuth provider instance
     */
    public function getProvider(string $provider): object
    {
        if (!$this->isProviderConfigured($provider)) {
            throw new \Exception("OAuth provider '{$provider}' not configured");
        }

        return match($provider) {
            'google' => new Google([
                'clientId'     => config("oauth::{$provider}::client_id"),
                'clientSecret' => config("oauth::{$provider}::client_secret"),
                'redirectUri'  => config("oauth::{$provider}::redirect_uri"),
            ]),
            'facebook' => new Facebook([
                'clientId'          => config("oauth::{$provider}::client_id"),
                'clientSecret'      => config("oauth::{$provider}::client_secret"),
                'redirectUri'       => config("oauth::{$provider}::redirect_uri"),
                'graphApiVersion'   => 'v18.0',
            ]),
            'x' => new GenericProvider([
                'clientId'                => config("oauth::{$provider}::client_id"),
                'clientSecret'            => config("oauth::{$provider}::client_secret"),
                'redirectUri'             => config("oauth::{$provider}::redirect_uri"),
                'urlAuthorize'            => 'https://twitter.com/i/oauth2/authorize',
                'urlAccessToken'          => 'https://api.twitter.com/2/oauth2/token',
                'urlResourceOwnerDetails' => 'https://api.twitter.com/2/users/me',
                'scopes'                  => 'tweet.read users.read',
            ]),
            default => throw new \Exception("Unsupported OAuth provider: {$provider}")
        };
    }

    /**
     * Get authorization URL for provider
     */
    public function getAuthorizationUrl(string $provider): string
    {
        $oauthProvider = $this->getProvider($provider);
        
        $options = [];
        if ($provider === 'google') {
            $options['scope'] = ['email', 'profile'];
        } elseif ($provider === 'facebook') {
            $options['scope'] = ['email', 'public_profile'];
        }

        $authUrl = $oauthProvider->getAuthorizationUrl($options);
        
        // Store state in session for CSRF protection
        $_SESSION['oauth_state'] = $oauthProvider->getState();
        $_SESSION['oauth_provider'] = $provider;
        
        return $authUrl;
    }

    /**
     * Handle OAuth callback and get user data
     */
    public function handleCallback(string $provider, string $code, string $state): array
    {
        // Verify state for CSRF protection
        if (empty($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            throw new \Exception('Invalid OAuth state');
        }

        $oauthProvider = $this->getProvider($provider);
        
        try {
            // Get access token
            $accessToken = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            // Get user data
            $userData = $this->getUserData($provider, $oauthProvider, $accessToken);
            
            // Clear OAuth session data
            unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
            
            return [
                'provider' => $provider,
                'provider_id' => $userData['id'],
                'email' => $userData['email'],
                'name' => $userData['name'],
                'avatar' => $userData['avatar'] ?? '',
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires' => $accessToken->getExpires() ? date('Y-m-d H:i:s', $accessToken->getExpires()) : null,
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("OAuth callback failed: " . $e->getMessage());
        }
    }

    /**
     * Get user data from provider
     */
    private function getUserData(string $provider, object $oauthProvider, object $accessToken): array
    {
        if ($provider === 'x') {
            // X (Twitter) requires custom handling
            return $this->getTwitterUserData($accessToken->getToken());
        }

        $resourceOwner = $oauthProvider->getResourceOwner($accessToken);
        $data = $resourceOwner->toArray();

        return match($provider) {
            'google' => [
                'id' => $data['sub'] ?? $data['id'],
                'email' => $data['email'],
                'name' => $data['name'],
                'avatar' => $data['picture'] ?? '',
            ],
            'facebook' => [
                'id' => $data['id'],
                'email' => $data['email'] ?? '',
                'name' => $data['name'],
                'avatar' => $data['picture']['data']['url'] ?? '',
            ],
            default => throw new \Exception("Unsupported provider: {$provider}")
        };
    }

    /**
     * Get Twitter/X user data
     */
    private function getTwitterUserData(string $accessToken): array
    {
        $ch = curl_init('https://api.twitter.com/2/users/me?user.fields=profile_image_url');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Failed to fetch Twitter user data");
        }
        
        $data = json_decode($response, true);
        $user = $data['data'] ?? [];
        
        return [
            'id' => $user['id'],
            'email' => '', // Twitter API v2 doesn't provide email by default
            'name' => $user['name'] ?? $user['username'],
            'avatar' => $user['profile_image_url'] ?? '',
        ];
    }

    /**
     * Check if a provider is configured
     */
    public function isProviderConfigured(string $provider): bool
    {
        $clientId = "oauth::{$provider}::client_id";
        $clientSecret = "oauth::{$provider}::client_secret";

        return !empty(config($clientId)) &&
               !empty(config($clientSecret));
    }
}
