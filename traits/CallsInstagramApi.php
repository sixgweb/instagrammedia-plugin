<?php

namespace Sixgweb\InstagramMedia\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sixgweb\InstagramMedia\Models\Settings;
use Exception;

/**
 * CallsInstagramApi Trait
 * 
 * Provides methods for interacting with the Instagram Graph API
 */
trait CallsInstagramApi
{
    /**
     * @var string Instagram Graph API base URL
     */
    protected $apiBaseUrl = 'https://graph.instagram.com';

    /**
     * Get the Guzzle HTTP client
     */
    protected function getHttpClient(): Client
    {
        return new Client([
            'base_uri' => $this->apiBaseUrl,
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Get settings instance
     */
    protected function getSettings(): Settings
    {
        return Settings::instance();
    }

    /**
     * Check if API is properly configured
     */
    protected function isConfigured(): bool
    {
        $settings = $this->getSettings();
        return $settings->hasValidCredentials() && !$settings->isTokenExpired();
    }

    /**
     * Get user's media from Instagram Graph API
     * 
     * @param int $limit Number of media items to fetch (max 100)
     * @return array
     * @throws Exception
     */
    protected function getUserMedia(int $limit = 25): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Instagram API is not properly configured or token is expired.');
        }

        $settings = $this->getSettings();
        $accessToken = $settings->get('access_token');

        // Fields to request from the API
        $fields = [
            'id',
            'media_type',
            'media_url',
            'thumbnail_url',
            'permalink',
            'caption',
            'timestamp',
            'username',
            'like_count',
            'comments_count'
        ];

        try {
            $client = $this->getHttpClient();
            $response = $client->get('/me/media', [
                'query' => [
                    'fields' => implode(',', $fields),
                    'limit' => min($limit, 100), // API max is 100
                    'access_token' => $accessToken
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? [];
        } catch (RequestException $e) {
            $message = 'Instagram API request failed';

            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message .= ': ' . ($errorBody['error']['message'] ?? $e->getMessage());
            }

            throw new Exception($message);
        }
    }

    /**
     * Get a specific media item by ID
     * 
     * @param string $mediaId Instagram media ID
     * @return array|null
     * @throws Exception
     */
    protected function getMediaById(string $mediaId): ?array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Instagram API is not properly configured or token is expired.');
        }

        $settings = $this->getSettings();
        $accessToken = $settings->get('access_token');

        $fields = [
            'id',
            'media_type',
            'media_url',
            'thumbnail_url',
            'permalink',
            'caption',
            'timestamp',
            'username',
            'like_count',
            'comments_count'
        ];

        try {
            $client = $this->getHttpClient();
            $response = $client->get("/{$mediaId}", [
                'query' => [
                    'fields' => implode(',', $fields),
                    'access_token' => $accessToken
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            throw new Exception('Failed to fetch media: ' . $e->getMessage());
        }
    }

    /**
     * Refresh a long-lived access token
     * 
     * @return array Token data with new access_token
     * @throws Exception
     */
    protected function refreshAccessToken(): array
    {
        $settings = $this->getSettings();
        $accessToken = $settings->get('access_token');

        if (empty($accessToken)) {
            throw new Exception('No access token to refresh');
        }

        try {
            $client = $this->getHttpClient();
            $response = $client->get('/refresh_access_token', [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $accessToken
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data;
        } catch (RequestException $e) {
            throw new Exception('Failed to refresh access token: ' . $e->getMessage());
        }
    }

    /**
     * Get user profile information
     * 
     * @return array
     * @throws Exception
     */
    protected function getUserProfile(): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Instagram API is not properly configured or token is expired.');
        }

        $settings = $this->getSettings();
        $accessToken = $settings->get('access_token');

        try {
            $client = $this->getHttpClient();
            $response = $client->get('/me', [
                'query' => [
                    'fields' => 'id,username,account_type,media_count',
                    'access_token' => $accessToken
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Exception('Failed to fetch user profile: ' . $e->getMessage());
        }
    }
}
