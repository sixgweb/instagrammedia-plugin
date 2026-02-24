<?php

namespace Sixgweb\InstagramMedia\Components;

use Cms\Classes\ComponentBase;
use Sixgweb\InstagramMedia\Models\Media;

/**
 * MediaList Component
 * 
 * Displays Instagram media on the frontend
 */
class MediaList extends ComponentBase
{
    /**
     * @var Collection Media items
     */
    public $media;

    /**
     * Component details
     */
    public function componentDetails()
    {
        return [
            'name' => 'Instagram Media List',
            'description' => 'Displays Instagram media feed'
        ];
    }

    /**
     * Component properties
     */
    public function defineProperties()
    {
        return [
            'limit' => [
                'title' => 'Limit',
                'description' => 'Number of media items to display',
                'type' => 'string',
                'default' => '12',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'The Limit must be a number'
            ],
            'mediaType' => [
                'title' => 'Media Type',
                'description' => 'Filter by media type',
                'type' => 'dropdown',
                'default' => 'all',
                'options' => [
                    'all' => 'All Types',
                    'IMAGE' => 'Images Only',
                    'VIDEO' => 'Videos Only',
                    'CAROUSEL_ALBUM' => 'Carousels Only'
                ]
            ],
            'sortOrder' => [
                'title' => 'Sort Order',
                'description' => 'How to sort the media',
                'type' => 'dropdown',
                'default' => 'timestamp desc',
                'options' => [
                    'timestamp desc' => 'Newest First',
                    'timestamp asc' => 'Oldest First',
                    'like_count desc' => 'Most Liked',
                    'comments_count desc' => 'Most Commented'
                ]
            ],
            'displayType' => [
                'title' => 'Display Type',
                'description' => 'How to display the media',
                'type' => 'dropdown',
                'default' => 'grid',
                'options' => [
                    'grid' => 'Grid',
                    'masonry' => 'Masonry',
                    'carousel' => 'Carousel',
                    'list' => 'List'
                ]
            ]
        ];
    }

    public function init()
    {
        $this->prepareVars();
    }
    /**
     * Run when component is initialized
     */
    public function prepareVars()
    {
        $this->page['instagramMedia'] = $this->loadMedia();
    }

    /**
     * Load media from database
     */
    protected function loadMedia()
    {
        $query = Media::isVisible();

        // Filter by media type
        if ($this->property('mediaType') !== 'all') {
            $query->where('media_type', $this->property('mediaType'));
        }

        // Sort
        $sortOrder = explode(' ', $this->property('sortOrder'));
        $query->orderBy($sortOrder[0], $sortOrder[1] ?? 'desc');

        // Limit
        $limit = (int) $this->property('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get display type for template
     */
    public function displayType()
    {
        return $this->property('displayType');
    }
}
