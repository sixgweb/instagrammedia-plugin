<?php

namespace Sixgweb\InstagramMedia\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sixgweb\InstagramMedia\Models\Settings;
use Carbon\Carbon;
use Exception;

/**
 * ManagesOAuth Trait
 * 
 * Handles OAuth authorization flow and token management
 */
trait ManagesOAuth
{
    /**
     * @var string OAuth authorization base URL
     */
    protected $oauthBaseUrl = 'https://api.instagram.com/oauth/';

    /**
     * @var string Graph API base URL for token operations
     */
    protected $graphBaseUrl = 'https://graph.instagram.com/';

    /**
     * Get the authorization URL for Instagram OAuth
     * 
     * @param string $redirectUri Callback URL
     * @param string $state CSRF protection state
     * @return string
     */
    protected function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $settings = Settings::instance();
        $appId = $settings->get('app_id');

        if (empty($appId)) {
            throw new Exception('App ID not configured');
        }

        $params = [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'instagram_business_basic',
            'response_type' => 'code',
            'state' => $state
        ];

        $query = '';
        foreach ($params as $key => $value) {
            if (!empty($query)) {
                $query .= '&';
            }
            $query .= $key . '=' . $value;
        }

        return $this->oauthBaseUrl . 'authorize?' . $query;
    }

    /**
     * Exchange authorization code for short-lived access token
     * 
     * @param string $code Authorization code
     * @param string $redirectUri Callback URL (must match authorization URL)
     * @return array Token data
     * @throws Exception
     */
    protected function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $appId = Settings::get('app_id');
        $appSecret = Settings::get('app_secret');

        if (empty($appId) || empty($appSecret)) {
            throw new Exception('App credentials not configured');
        }

        try {
            $client = new Client([
                'base_uri' => $this->oauthBaseUrl,
                'timeout' => 30,
            ]);

            $response = $client->post('access_token', [
                'form_params' => [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code
                ]
            ]);


            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['access_token'])) {
                trace_log($response->getStatusCode(), $appId, $appSecret, $redirectUri, $code, $response->getBody()->getContents());
                throw new Exception('No access token received');
            }

            return $data;
        } catch (RequestException $e) {
            $message = 'Token exchange failed';

            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message .= ': ' . ($errorBody['error_message'] ?? $e->getMessage());
            }

            throw new Exception($message);
        }
    }

    /**
     * Exchange short-lived token for long-lived token (60 days)
     * 
     * @param string $shortLivedToken Short-lived access token
     * @return array Token data with access_token and expires_in
     * @throws Exception
     */
    protected function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $settings = Settings::instance();
        $appSecret = $settings->get('app_secret');

        if (empty($appSecret)) {
            throw new Exception('App secret not configured');
        }

        try {
            $client = new Client([
                'base_uri' => $this->graphBaseUrl,
                'timeout' => 30,
            ]);

            $response = $client->get('access_token', [
                'query' => [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => $appSecret,
                    'access_token' => $shortLivedToken
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['access_token'])) {
                throw new Exception('No long-lived token received');
            }

            return $data;
        } catch (RequestException $e) {
            throw new Exception('Long-lived token exchange failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh a long-lived access token (extends for another 60 days)
     * 
     * @param string|null $accessToken Token to refresh (uses stored token if null)
     * @return array Token data with access_token and expires_in
     * @throws Exception
     */
    protected function refreshLongLivedToken(?string $accessToken = null): array
    {
        $settings = Settings::instance();

        if ($accessToken === null) {
            $accessToken = $settings->get('access_token');
        }

        if (empty($accessToken)) {
            throw new Exception('No access token to refresh');
        }

        try {
            $client = new Client([
                'base_uri' => $this->graphBaseUrl,
                'timeout' => 30,
            ]);

            $response = $client->get('refresh_access_token', [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $accessToken
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['access_token'])) {
                throw new Exception('No refreshed token received');
            }

            return $data;
        } catch (RequestException $e) {
            throw new Exception('Token refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Complete OAuth flow and save tokens
     * 
     * @param string $code Authorization code
     * @param string $redirectUri Callback URL
     * @return bool Success
     * @throws Exception
     */
    protected function completeOAuthFlow(string $code, string $redirectUri): bool
    {
        $settings = Settings::instance();

        // Step 1: Exchange code for short-lived token
        $shortLivedData = $this->exchangeCodeForToken($code, $redirectUri);

        // Step 2: Exchange short-lived for long-lived token
        $longLivedData = $this->exchangeForLongLivedToken($shortLivedData['access_token']);

        // Step 3: Get user info
        try {
            $userInfo = $this->getUserProfileWithToken($longLivedData['access_token']);
            $username = $userInfo['username'] ?? null;
        } catch (Exception $e) {
            $username = null;
        }

        // Step 4: Save to settings
        $expiresIn = $longLivedData['expires_in'] ?? 5184000; // Default 60 days
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        $settings->set('access_token', $longLivedData['access_token']);
        $settings->set('token_expires_at', $expiresAt);
        $settings->set('username', $username);
        $settings->set('last_token_refresh', Carbon::now());
        $settings->save();

        return true;
    }

    /**
     * Refresh the stored access token
     * 
     * @return bool Success
     * @throws Exception
     */
    protected function refreshStoredToken(): bool
    {
        $settings = Settings::instance();

        $tokenData = $this->refreshLongLivedToken();

        $expiresIn = $tokenData['expires_in'] ?? 5184000; // Default 60 days
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        $settings->set('access_token', $tokenData['access_token']);
        $settings->set('token_expires_at', $expiresAt);
        $settings->set('last_token_refresh', Carbon::now());
        $settings->save();

        return true;
    }

    /**
     * Check if token needs refresh (within 7 days of expiry)
     * 
     * @return bool
     */
    protected function tokenNeedsRefresh(): bool
    {
        $settings = Settings::instance();
        $expiresAt = $settings->get('token_expires_at');

        if (!$expiresAt) {
            return false;
        }

        // Refresh if expiring within 7 days
        return $expiresAt->diffInDays(Carbon::now()) <= 7;
    }

    /**
     * Get user profile with specific token
     * 
     * @param string $accessToken
     * @return array
     * @throws Exception
     */
    protected function getUserProfileWithToken(string $accessToken): array
    {
        try {
            $client = new Client([
                'base_uri' => $this->graphBaseUrl,
                'timeout' => 30,
            ]);

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
