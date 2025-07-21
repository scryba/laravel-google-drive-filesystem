<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Google Drive API settings. You will need
    | to create a project in Google Cloud Console and enable the Drive API.
    |
    */

    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', 'http://localhost'),
    'access_token' => env('GOOGLE_DRIVE_ACCESS_TOKEN'),
    'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    
    /*
    |--------------------------------------------------------------------------
    | Google Drive Folder ID
    |--------------------------------------------------------------------------
    |
    | The folder ID where files should be stored. If not set, files will be
    | stored in the root directory of Google Drive.
    |
    */
    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
];
