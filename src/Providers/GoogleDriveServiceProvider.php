<?php

namespace Scryba\GoogleDriveFilesystem\Providers;

use Google\Client;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Scryba\GoogleDriveFilesystem\Adapters\GoogleDriveAdapter;
use League\Flysystem\Filesystem;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/google-drive.php', 'filesystems.disks.google');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/google-drive.php' => config_path('google-drive.php'),
        ], 'google-drive-config');

        Storage::extend('google', function ($app, $config) {
            $client = new Client();
            
            // Set up Google API client
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);
            $client->setRedirectUri($config['redirect_uri']);
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setScopes(['https://www.googleapis.com/auth/drive']);

            // Handle authentication tokens
            if (!empty($config['refresh_token'])) {
                // Fetch and set the access token using the refresh token
                $accessToken = $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);
                $client->setAccessToken($accessToken);
            } elseif (!empty($config['access_token'])) {
                // Set the access token directly if provided
                $client->setAccessToken($config['access_token']);
            }

            // Get folder ID from config or env
            $folderId = $config['folder_id'] ?? env('GOOGLE_DRIVE_FOLDER_ID');

            $adapter = new GoogleDriveAdapter($client, $folderId);
            $filesystem = new Filesystem($adapter);

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
