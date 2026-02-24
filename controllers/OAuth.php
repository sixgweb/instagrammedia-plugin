<?php

namespace Sixgweb\InstagramMedia\Controllers;

use Backend\Classes\Controller;
use Sixgweb\InstagramMedia\Models\Settings;
use Sixgweb\InstagramMedia\Traits\ManagesOAuth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Backend;
use Flash;
use Exception;

/**
 * OAuth Controller
 * 
 * Handles Instagram OAuth authorization flow
 */
class OAuth extends Controller
{
    use ManagesOAuth;

    /**
     * No backend menu context needed for OAuth
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Initiate OAuth authorization flow
     */
    public function authorize()
    {
        try {
            $settings = Settings::instance();

            if (empty($settings->get('app_id'))) {
                throw new Exception('Instagram App ID not configured. Please configure it in settings first.');
            }

            // Generate state for CSRF protection
            $state = bin2hex(random_bytes(16));
            Session::put('instagram_oauth_state', $state);

            // Build redirect URI
            $redirectUri = Backend::url('sixgweb/instagrammedia/oauth/callback');

            // Get authorization URL
            $authUrl = $this->getAuthorizationUrl($redirectUri, $state);

            // Redirect to Instagram
            return Redirect::to($authUrl);
        } catch (Exception $e) {
            Flash::error('Authorization failed: ' . $e->getMessage());
            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        }
    }

    /**
     * Handle OAuth callback from Instagram
     */
    public function callback()
    {
        try {
            // Verify state for CSRF protection
            $state = input('state');
            $sessionState = Session::get('instagram_oauth_state');

            if (empty($state) || $state !== $sessionState) {
                throw new Exception('Invalid state parameter. Possible CSRF attack.');
            }

            // Check for errors
            if ($error = input('error')) {
                $errorDescription = input('error_description', 'Unknown error');
                throw new Exception("Authorization failed: {$errorDescription}");
            }

            // Get authorization code
            $code = input('code');
            if (empty($code)) {
                throw new Exception('No authorization code received');
            }

            // Build redirect URI (must match authorization request)
            $redirectUri = Backend::url('sixgweb/instagrammedia/oauth/callback');

            // Complete OAuth flow
            $this->completeOAuthFlow($code, $redirectUri);

            // Clear session state
            Session::forget('instagram_oauth_state');

            Flash::success('Successfully connected to Instagram! You can now sync your media.');

            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        } catch (Exception $e) {
            Flash::error('OAuth callback failed: ' . $e->getMessage());
            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        }
    }

    /**
     * Manually refresh the access token
     */
    public function refresh()
    {
        try {
            $this->refreshStoredToken();

            Flash::success('Access token refreshed successfully! Valid for another 60 days.');

            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        } catch (Exception $e) {
            Flash::error('Token refresh failed: ' . $e->getMessage());
            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        }
    }

    /**
     * Disconnect Instagram account
     */
    public function disconnect()
    {
        try {
            $settings = Settings::instance();
            $settings->set('access_token', null);
            $settings->set('token_expires_at', null);
            $settings->set('username', null);
            $settings->set('last_token_refresh', null);
            $settings->save();

            Flash::success('Instagram account disconnected successfully.');

            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        } catch (Exception $e) {
            Flash::error('Disconnect failed: ' . $e->getMessage());
            return Redirect::to(Backend::url('system/settings/update/sixgweb/instagrammedia/settings'));
        }
    }
}
