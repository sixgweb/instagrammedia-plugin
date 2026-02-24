<?php

namespace Sixgweb\InstagramMedia;

use Backend;
use System\Classes\PluginBase;

/**
 * InstagramMedia Plugin
 *
 * Fetches and stores Instagram media from graph.instagram.com API
 *
 * @link https://docs.octobercms.com/4.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Instagram Media',
            'description' => 'Fetches and stores Instagram media from the Graph API',
            'author' => 'Sixgweb',
            'icon' => 'icon-instagram'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        $this->registerConsoleCommand('instagrammedia:sync', \Sixgweb\InstagramMedia\Console\SyncMedia::class);
        $this->registerConsoleCommand('instagrammedia:refresh-token', \Sixgweb\InstagramMedia\Console\RefreshToken::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        // Register backend routes for OAuth
        \Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            \Route::group(['prefix' => \Config::get('cms.backendUri', 'backend')], function () {
                \Route::get(
                    'sixgweb/instagrammedia/oauth/authorize',
                    [\Sixgweb\InstagramMedia\Controllers\OAuth::class, 'authorize']
                )
                    ->name('instagrammedia.oauth.authorize');

                \Route::get(
                    'sixgweb/instagrammedia/oauth/callback',
                    [\Sixgweb\InstagramMedia\Controllers\OAuth::class, 'callback']
                )
                    ->name('instagrammedia.oauth.callback');

                \Route::get(
                    'sixgweb/instagrammedia/oauth/refresh',
                    [\Sixgweb\InstagramMedia\Controllers\OAuth::class, 'refresh']
                )
                    ->name('instagrammedia.oauth.refresh');

                \Route::get(
                    'sixgweb/instagrammedia/oauth/disconnect',
                    [\Sixgweb\InstagramMedia\Controllers\OAuth::class, 'disconnect']
                )
                    ->name('instagrammedia.oauth.disconnect');
            });
        });
    }

    /**
     * registerSchedule for automatic token refresh
     */
    public function registerSchedule($schedule)
    {
        // Run token refresh check daily
        $schedule->call(function () {
            \Artisan::call('instagrammedia:refresh-token');
        })->daily()->name('instagrammedia-refresh-token');

        // Run media sync based on settings
        $settings = \Sixgweb\InstagramMedia\Models\Settings::instance();
        if ($settings->get('auto_sync_enabled')) {
            $frequency = $settings->get('sync_frequency', 'daily');

            $task = $schedule->call(function () {
                \Artisan::call('instagrammedia:sync');
            })->name('instagrammedia-auto-sync');

            switch ($frequency) {
                case 'hourly':
                    $task->hourly();
                    break;
                case 'weekly':
                    $task->weekly();
                    break;
                default:
                    $task->daily();
                    break;
            }
        }
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Sixgweb\InstagramMedia\Components\MediaList' => 'instagramMediaList',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'sixgweb.instagrammedia.access_media' => [
                'tab' => 'Instagram Media',
                'label' => 'Manage Instagram Media'
            ],
            'sixgweb.instagrammedia.access_settings' => [
                'tab' => 'Instagram Media',
                'label' => 'Manage Instagram Media Settings'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'instagrammedia' => [
                'label' => 'Instagram Media',
                'url' => Backend::url('sixgweb/instagrammedia/media'),
                'icon' => 'icon-instagram',
                'permissions' => ['sixgweb.instagrammedia.access_media'],
                'order' => 500,
            ]
        ];
    }

    /**
     * registerSettings used by the backend.
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Instagram Media',
                'description' => 'Configure Instagram API credentials',
                'category' => 'Social Media',
                'icon' => 'icon-instagram',
                'class' => 'Sixgweb\InstagramMedia\Models\Settings',
                'order' => 500,
                'permissions' => ['sixgweb.instagrammedia.access_settings']
            ]
        ];
    }
}
