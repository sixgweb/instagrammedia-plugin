<?php

namespace Sixgweb\InstagramMedia\Models;

use October\Rain\Database\Model;

/**
 * Media Model
 *
 * Stores Instagram media items from the Graph API
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Media extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'sixgweb_instagrammedia_media';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'instagram_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'caption',
        'timestamp',
        'username',
        'like_count',
        'comments_count',
        'is_visible'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'instagram_id' => 'required|unique:sixgweb_instagrammedia_media',
        'media_type' => 'required|in:IMAGE,VIDEO,CAROUSEL_ALBUM',
        'media_url' => 'required|url',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'is_visible' => 'boolean',
        'like_count' => 'integer',
        'comments_count' => 'integer',
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'timestamp'
    ];

    /**
     * Scope a query to only include visible media.
     */
    public function scopeIsVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to order by Instagram timestamp.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    /**
     * Get display name for media type
     */
    public function getMediaTypeDisplayAttribute()
    {
        return match ($this->media_type) {
            'IMAGE' => 'Image',
            'VIDEO' => 'Video',
            'CAROUSEL_ALBUM' => 'Carousel',
            default => $this->media_type
        };
    }
}
