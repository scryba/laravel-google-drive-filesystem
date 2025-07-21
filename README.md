# scryba/laravel-google-drive-filesystem

Google Drive filesystem adapter for Laravel 10, 11, and 12.

- **Author:** Michael K. Laweh (<contact@michael.laweitech.com>)
- **Homepage:** <https://michael.laweitech.com/>
- **Repository:** <https://github.com/scryba/laravel-google-drive-filesystem>
- **Funding:** [Buy me a coffee](https://michael.laweitech.com/buy-me-a-coffee)

## Installation

### Via Packagist (Recommended)

Install via Composer:

```
composer require scryba/laravel-google-drive-filesystem
```

### Via VCS (Development)

If you want to use the latest development version directly from GitHub

Add the following to your `composer.json`:

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/scryba/laravel-google-drive-filesystem"
    }
],
"require": {
    "scryba/laravel-google-drive-filesystem": "@dev"
}
```

Then run:

```
composer update
```

## Configuration

Publish the config file:

```
php artisan vendor:publish --tag=google-drive-config
```

Add your Google Drive credentials and folder ID to your `.env`:

```
GOOGLE_DRIVE_CLIENT_ID=your-client-id
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret
GOOGLE_DRIVE_REFRESH_TOKEN=your-refresh-token
GOOGLE_DRIVE_ACCESS_TOKEN=your-access-token
GOOGLE_DRIVE_FOLDER_ID=your-folder-id (optional)
```

### How to Obtain Google Drive API Credentials

1. **Create a Google Cloud Project & Enable Drive API:**
   - Go to the [Google Cloud Console](https://console.cloud.google.com/).
   - Create a new project (or select an existing one).
   - Go to "APIs & Services" > "Library" and enable the [Google Drive API](https://console.developers.google.com/apis/library/drive.googleapis.com).

2. **Create OAuth 2.0 Credentials:**
   - Go to "APIs & Services" > "Credentials".
   - Click "Create Credentials" > "OAuth client ID".
   - Choose "Desktop app" or "Web application" (for server-side apps).
   - Download the credentials JSON file.
   - Your **Client ID** and **Client Secret** are in this file.
   - [Google OAuth 2.0 Guide](https://developers.google.com/identity/protocols/oauth2)

3. **Obtain Refresh Token and Access Token:**
   - Use a tool like [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/) or your own script to authorize your app and get tokens.
   - [Guide: Get Refresh Token with OAuth Playground](https://stackoverflow.com/a/61592974)
   - The **Refresh Token** allows your app to obtain new access tokens automatically.

4. **Get Google Drive Folder ID (Optional):**
   - Open the folder in Google Drive and copy the last part of the URL (after `folders/`).
   - Example: `https://drive.google.com/drive/folders/<your-folder-id>`

> For more details, see the [Google Drive API documentation](https://developers.google.com/drive/api/v3/about-auth).

- If `GOOGLE_DRIVE_FOLDER_ID` is set, files will be stored in that folder.
- If not set or not found, files will be stored in the root directory of your Google Drive.

## Usage

After configuring, you can use the Google Drive disk in your Laravel application like this:

```php
use Illuminate\Support\Facades\Storage;

// Store a file
Storage::disk('google')->put('example.txt', 'Hello, Google Drive!');

// Retrieve a file
$content = Storage::disk('google')->get('example.txt');

// List files in a directory
$files = Storage::disk('google')->files('/');

// Delete a file
Storage::disk('google')->delete('example.txt');
```

## Laravel Compatibility

- Laravel 10.x, 11.x, 12.x
- PHP 8.1+

## License

MIT

## Support

For issues, bug reports, or feature requests, please visit the [GitHub Issues page](https://github.com/scryba/laravel-google-drive-filesystem/issues).
