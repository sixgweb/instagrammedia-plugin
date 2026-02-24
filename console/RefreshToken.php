<?php

namespace Sixgweb\InstagramMedia\Console;

use Illuminate\Console\Command;
use Sixgweb\InstagramMedia\Models\Settings;
use Sixgweb\InstagramMedia\Traits\ManagesOAuth;
use Carbon\Carbon;
use Exception;

/**
 * RefreshToken Command
 * 
 * Automatically refreshes Instagram access token if needed
 */
class RefreshToken extends Command
{
    use ManagesOAuth;

    /**
     * @var string The console command name.
     */
    protected $signature = 'instagrammedia:refresh-token 
                            {--force : Force refresh even if not needed}';

    /**
     * @var string The console command description.
     */
    protected $description = 'Refresh Instagram access token if expiring soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = Settings::instance();

        // Check if we have a token
        if (empty($settings->get('access_token'))) {
            $this->warn('No access token found. Please authorize the plugin first.');
            return 1;
        }

        $expiresAt = $settings->get('token_expires_at');

        if (!$expiresAt) {
            $this->warn('Token expiration date not set.');
            return 1;
        }

        // Check if refresh is needed (within 7 days of expiry) or forced
        $daysUntilExpiry = $expiresAt->diffInDays(Carbon::now(), false);
        $needsRefresh = $daysUntilExpiry <= 7 && $daysUntilExpiry >= 0;

        if (!$needsRefresh && !$this->option('force')) {
            $this->info("Token is still valid for {$daysUntilExpiry} days. No refresh needed.");
            $this->info("Token expires at: {$expiresAt->toDateTimeString()}");
            return 0;
        }

        if ($daysUntilExpiry < 0) {
            $this->error('Token has already expired. Please re-authorize the plugin.');
            return 1;
        }

        try {
            $this->info('Refreshing Instagram access token...');

            // Refresh the token
            $this->refreshStoredToken();

            $newExpiresAt = $settings->get('token_expires_at');
            $this->info('✓ Token refreshed successfully!');
            $this->info("  New expiration: {$newExpiresAt->toDateTimeString()}");
            $this->info("  Valid for another 60 days.");

            return 0;
        } catch (Exception $e) {
            $this->error('Token refresh failed: ' . $e->getMessage());
            $this->error('Please re-authorize the plugin if the error persists.');
            return 1;
        }
    }
}
