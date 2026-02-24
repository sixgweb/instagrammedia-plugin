<?php

namespace Sixgweb\InstagramMedia\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Sixgweb\InstagramMedia\Models\Media as MediaModel;

/**
 * Media Backend Controller
 *
 * @link https://docs.octobercms.com/4.x/extend/system/controllers.html
 */
class Media extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['sixgweb.instagrammedia.access_media'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Sixgweb.InstagramMedia', 'instagrammedia', 'media');
    }

    /**
     * Sync media from Instagram API
     */
    public function onSyncMedia()
    {
        try {
            \Artisan::call('instagrammedia:sync');

            \Flash::success('Instagram media sync completed successfully!');
        } catch (\Exception $e) {
            \Flash::error('Sync failed: ' . $e->getMessage());
        }

        return $this->listRefresh();
    }

    /**
     * Bulk toggle visibility
     */
    public function index_onToggleVisibility()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds)) {
            \Flash::error('Please select items to toggle visibility');
            return;
        }

        $count = 0;
        foreach ($checkedIds as $id) {
            if ($media = MediaModel::find($id)) {
                $media->is_visible = !$media->is_visible;
                $media->save();
                $count++;
            }
        }

        \Flash::success("Toggled visibility for {$count} items");
        return $this->listRefresh();
    }
}
