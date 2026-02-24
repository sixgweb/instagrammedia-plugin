<?php

namespace Sixgweb\InstagramMedia\Models;

use System\Models\SettingModel;

/**
 * Settings Model
 *
 * Stores Instagram API credentials and configuration
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Settings extends SettingModel
{
    use \October\Rain\Database\Traits\Encryptable;

    /**
     * @var string settingsCode unique code for this settings model
     */
    public $settingsCode = 'sixgweb_instagrammedia_settings';

    /**
     * @var string settingsFields form field definitions
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array encryptable list of attributes to encrypt
     */
    protected $encryptable = [
        'access_token',
        'app_secret'
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    public $dates = [
        'token_expires_at',
        'last_token_refresh'
    ];

    /**
     * Check if we have valid API credentials
     */
    public function hasValidCredentials(): bool
    {
        return !empty($this->get('access_token'))
            && !empty($this->get('app_id'));
    }

    /**
     * Check if the access token is expired
     */
    public function isTokenExpired(): bool
    {
        $expiresAt = $this->get('token_expires_at');

        if (!$expiresAt) {
            return true;
        }

        return $expiresAt->isPast();
    }

    /**
     * Check if token needs refresh (within 7 days of expiry)
     */
    public function tokenNeedsRefresh(): bool
    {
        $expiresAt = $this->get('token_expires_at');

        if (!$expiresAt) {
            return false;
        }

        // Refresh if expiring within 7 days
        return $expiresAt->diffInDays(\Carbon\Carbon::now()) <= 7;
    }

    /**
     * Get fields to filter based on configuration
     */
    public function filterFields($fields, $context = null)
    {
        $hasToken = !empty($this->get('access_token'));

        // Show credential warning if not configured
        if (!$this->hasValidCredentials()) {
            $fields->_credentials_warning->hidden = false;
        } else {
            $fields->_credentials_warning->hidden = true;
        }

        // Show token expiry warning if expired
        if ($this->isTokenExpired() && $this->hasValidCredentials()) {
            $fields->_token_expired_warning->hidden = false;
        } else {
            $fields->_token_expired_warning->hidden = true;
        }

        // Show last refresh field only if we have a refresh date
        if (empty($this->get('last_token_refresh'))) {
            $fields->last_token_refresh->hidden = true;
        }
    }
}
