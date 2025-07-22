# Advanced Usage

## Storing Files

```php
Storage::disk('google')->put('folder/filename.txt', 'File contents');
```

## Retrieving Files

```php
$content = Storage::disk('google')->get('folder/filename.txt');
```

## Listing Files

```php
$files = Storage::disk('google')->files('folder');
```

## Deleting Files

```php
Storage::disk('google')->delete('folder/filename.txt');
```

## Working with Folders

- Folders are created automatically when you upload a file to a new path.
- You can list folders using:

```php
$folders = Storage::disk('google')->directories('/');
```

## Caveats

- Google Drive may not immediately report file sizes or modification dates for new uploads (see README note).
- Visibility settings are not supported (Google Drive does not have public/private in the same way as S3).
- For large files, consider chunked uploads (not yet supported by this adapter).

## More Examples

See the [Laravel Filesystem documentation](https://laravel.com/docs/filesystem) for more usage patterns.
