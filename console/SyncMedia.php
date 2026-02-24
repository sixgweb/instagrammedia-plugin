<?php

namespace Sixgweb\InstagramMedia\Console;

use Illuminate\Console\Command;
use Sixgweb\InstagramMedia\Models\Media;
use Sixgweb\InstagramMedia\Models\Settings;
use Sixgweb\InstagramMedia\Traits\CallsInstagramApi;
use Carbon\Carbon;
use Exception;

/**
 * SyncMedia Command
 * 
 * Syncs Instagram media from the Graph API to the database
 */
class SyncMedia extends Command
{
    use CallsInstagramApi;

    /**
     * @var string The console command name.
     */
    protected $signature = 'instagrammedia:sync 
                            {--limit=25 : Number of media items to fetch}
                            {--force : Force sync even if settings are not configured}';

    /**
     * @var string The console command description.
     */
    protected $description = 'Sync Instagram media from the Graph API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Instagram media sync...');

        // Check configuration
        if (!$this->isConfigured() && !$this->option('force')) {
            $this->error('Instagram API is not properly configured or token is expired.');
            $this->info('Please configure the plugin settings first.');
            return 1;
        }

        $settings = $this->getSettings();
        $limit = (int) $this->option('limit');

        try {
            // Fetch media from Instagram API
            $this->info("Fetching up to {$limit} media items from Instagram...");
            $mediaItems = $this->getUserMedia($limit);

            if (empty($mediaItems)) {
                $this->warn('No media items found.');
                return 0;
            }

            $this->info('Found ' . count($mediaItems) . ' media items. Processing...');

            $newCount = 0;
            $updatedCount = 0;
            $errorCount = 0;

            // Process each media item
            foreach ($mediaItems as $item) {
                try {
                    $media = Media::firstOrNew(['instagram_id' => $item['id']]);
                    $isNew = !$media->exists;

                    // Update media attributes
                    $media->fill([
                        'media_type' => $item['media_type'],
                        'media_url' => $item['media_url'],
                        'thumbnail_url' => $item['thumbnail_url'] ?? null,
                        'permalink' => $item['permalink'] ?? null,
                        'caption' => $item['caption'] ?? null,
                        'timestamp' => isset($item['timestamp']) ? Carbon::parse($item['timestamp']) : null,
                        'username' => $item['username'] ?? $settings->get('username'),
                        'like_count' => $item['like_count'] ?? 0,
                        'comments_count' => $item['comments_count'] ?? 0,
                    ]);

                    // Keep existing visibility setting for existing media
                    if ($isNew) {
                        $media->is_visible = true;
                    }

                    $media->save();

                    if ($isNew) {
                        $newCount++;
                    } else {
                        $updatedCount++;
                    }

                    $this->line("  ✓ Processed: {$item['id']} ({$item['media_type']})");
                } catch (Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Failed to process {$item['id']}: " . $e->getMessage());
                }
            }

            // Auto-hide old media if enabled
            if ($settings->get('auto_hide_old_media')) {
                $this->info('Checking for old media to hide...');
                $this->autoHideOldMedia();
            }

            // Summary
            $this->info('');
            $this->info('Sync completed!');
            $this->info("  New: {$newCount}");
            $this->info("  Updated: {$updatedCount}");

            if ($errorCount > 0) {
                $this->warn("  Errors: {$errorCount}");
            }

            return 0;
        } catch (Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Auto-hide media older than configured days
     */
    protected function autoHideOldMedia()
    {
        $settings = $this->getSettings();
        $days = (int) $settings->get('hide_after_days', 30);
        $cutoffDate = Carbon::now()->subDays($days);

        $count = Media::where('timestamp', '<', $cutoffDate)
            ->where('is_visible', true)
            ->update(['is_visible' => false]);

        if ($count > 0) {
            $this->info("  Hidden {$count} media items older than {$days} days");
        }
    }
}
