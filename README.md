# Instagram Media Plugin

Fetches and stores Instagram media from the Instagram Graph API for October CMS with OAuth authorization and automatic token management.

## Features

- 🔐 **OAuth Authorization** - Easy one-click Instagram connection
- 🔄 **Auto Token Refresh** - Automatically refreshes tokens before expiry
- 📸 **Automatic Media Sync** - Fetch Instagram media via Graph API
- 💾 **Database Storage** - Store media with metadata (likes, comments, captions)
- 👁️ **Visibility Control** - Show/hide individual media items
- 🎨 **Frontend Component** - Display media in customizable grid layouts
- ⚙️ **Configurable Settings** - API credentials, sync frequency, auto-hide old posts
- 🔧 **Artisan Commands** - Manual sync and token refresh via CLI
- 📅 **Scheduled Tasks** - Automated token refresh and media sync
- 🎯 **Filter Options** - Filter by media type, sort by date/engagement

## Installation

1. Place the plugin in `plugins/sixgweb/instagrammedia/`
2. Run migrations:
   ```bash
   php artisan plugin:refresh Sixgweb.InstagramMedia
   ```
Quick Setup with OAuth (Recommended)

1. Go to [Meta for Developers](https://developers.facebook.com/apps/)
2. Create a new Business app
3. Add **Instagram Basic Display** product
4. In Basic Display settings, add OAuth Redirect URI:
   ```
   https://yoursite.com/backend/sixgweb/instagrammedia/oauth/callback
   ```
5. Copy your App ID and App Secret
6. In October CMS:
   - Go to **Settings → Instagram Media**
   - Paste App ID and App Secret
   - Click **Save**
   - Click **Connect Instagram Account**
   - Authorize on Instagram
7. Done! Token is automatically managed

### Manual Configuration (Advanced)
## Configuration

### 1. Create Instagram App

1. Go to [Meta for Developers](https://developers.facebook.com/apps/)
2. Create a new Business app
3. Add **Instagram Basic Display** product
4. Note your App ID and App Secret

### 2. Generate Access Token

1. Add an Instagram Test User in your app
2. Generate a User Token
3. Exchange for long-lived token (60 days):
   ```
   https://graph.instagram.com/access_token?
     grant_type=ig_exchange_token&
     client_secret={app-secret}&
     access_token={short-lived-token}
   ```

### 3. Configure Plugin

1. Go to **Settings → Instagram Media**
2. Enter your App ID, App Secret, and Access Token
3. Set token expiration date (60 days from generation)
4. Save settings

## Usage

### Syncing Media

**Via Backend:**
- Navigate to **Instagram Media** in the backend menu
- Click "Sync from Instagram" button

**Via Artisan Command:**
```bash
php artisan instagrammedia:sync
php artisan instagrammedia:sync --limit=50
```

### Frontend Display

Add the component to any page:

```twig
[instagramMediaList]
limit = "12"
mediaType = "all"
sortOrder = "timestamp desc"
displayType = "grid"
==
{% component 'instagramMediaList' %}
```

#### Component Properties

- **limit** - Number of items to display (default: 12)
- **mediaType** - Filter: `all`, `IMAGE`, `VIDEO`, `CAROUSEL_ALBUM`
- **sortOrder** - Sort by: `timestamp desc`, `timestamp asc`, `like_count desc`, `comments_count desc`
- **displayType** - Layout: `grid`, `masonry`, `carousel`, `list`

## Backend Management

### Media List

- View all synced Instagram media
- Toggle visibility for individual items
- Bulk toggle visibility for multiple items
- View engagement metrics (likes, comments)

### Settings

- **API Configuration** - App credentials and access token
- **Sync Settings** - Auto-sync, frequency, media limit
- **Auto-hide** - Automatically hide posts older than X days

## API Reference

### Graph API Endpoints Used

- `GET /me/media` - Fetch user's media
- `GET /{media-id}` - Get specific media item
- `GET /refresh_access_token` - Refresh long-lived token

### Fields Retrieved

- `id`, `media_type`, `media_url`, `thumbnail_url`
- `permalink`, `caption`, `timestamp`
- `username`, `like_count`, `comments_count`

## Permissions

- `sixgweb.instagrammedia.access_media` - Manage media in backend
- `sixgweb.instagrammedia.access_settings` - Configure plugin settings

## Database Schema

Table: `sixgweb_instagrammedia_media`

- `instagram_id` - Unique Instagram media ID
- `media_type` - IMAGE, VIDEO, or CAROUSEL_ALBUM
- `media_url` - Full resolution media URL
- `thumbnail_url` - Thumbnail URL (for videos)
- `permalink` - Instagram post URL
- `caption` - Post caption text
- `timestamp` - When posted on Instagram
- `username` - Instagram username
- `like_count` - Number of likes
- `comments_count` - Number of comments
- `is_visible` - Display on frontend (boolean)

## Token Management

### Automatic Token Refresh

The plugin includes automatic token refresh:

- **Daily Checks**: Runs daily via scheduled task
- **Smart Refresh**: Only refreshes when within 7 days of expiry
- **Extends Validity**: Each refresh extends token for 60 more days

Make sure your cron is configured:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Token Management

**Refresh Token (Backend):**
- Go to Settings → Instagram Media
- Click "Refresh Token" button

**Refresh Token (CLI):**
```bash
php artisan instagrammedia:refresh-token
php artisan instagrammedia:refresh-token --force
```

**Disconnect Account:**
- Go to Settings → Instagram Media
- Click "Disconnect" button

### OAuth URLs

The plugin registers these OAuth endpoints:
- Authorization: `/backend/sixgweb/instagrammedia/oauth/authorize`
- Callback: `/backend/sixgweb/instagrammedia/oauth/callback`
- Refresh: `/backend/sixgweb/instagrammedia/oauth/refresh`
- Disconnect: `/backend/sixgweb/instagrammedia/oauth/disconnect`

## Troubleshooting

### "Token is expired"
- Generate a new long-lived token
- Update token expiration date in settings

### "No media items found"
- Verify your Instagram account has public posts
- Check that your access token has correct permissions
- Ensure the Instagram Test User is authorized

### Sync fails
- Check your API credentials
- Verify token hasn't expired
- Check Instagram API rate limits

## Requirements

- October CMS v4.x
- PHP ^8.2
- GuzzleHTTP (included with October)
- Valid Instagram Basic Display app
- Instagram Business or Creator account

## License

Proprietary - Sixgweb

## Support

For issues or feature requests, contact the development team.
