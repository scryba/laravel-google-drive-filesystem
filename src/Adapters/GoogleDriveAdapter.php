<?php

namespace Scryba\GoogleDriveFilesystem\Adapters;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

class GoogleDriveAdapter implements FilesystemAdapter
{
    private Drive $service;
    private PathPrefixer $prefixer;
    private ?string $rootFolderId;

    public function __construct(Client $client, ?string $rootFolderId = null)
    {
        $this->service = new Drive($client);
        $this->prefixer = new PathPrefixer('');
        $this->rootFolderId = $rootFolderId;
    }

    public function fileExists(string $path): bool
    {
        try {
            $file = $this->getFileByPath($path);
            return $file !== null;
        } catch (\Exception $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $folder = $this->getFolderByPath($path);
            return $folder !== null;
        } catch (\Exception $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $pathInfo = pathinfo($path);
            $parentId = $this->getOrCreateParentFolder($pathInfo['dirname'] ?? '');
            
            $file = new DriveFile();
            $file->setName($pathInfo['basename']);
            $file->setParents([$parentId]);

            $createdFile = $this->service->files->create($file, [
                'data' => $contents,
                'mimeType' => $this->getMimeType($path),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Fetch the file metadata once after upload
            $fileMeta = $this->service->files->get($createdFile->getId(), ['fields' => 'id,size,modifiedTime']);
            \Log::debug('[GoogleDriveAdapter] write: Fetched file metadata after upload', [
                'id' => $createdFile->getId(),
                'size' => $fileMeta->getSize(),
                'modifiedTime' => $fileMeta->getModifiedTime(),
            ]);
        } catch (\Exception $e) {
            \Log::error('[GoogleDriveAdapter] write error', ['path' => $path, 'error' => $e->getMessage()]);
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        try {
            $file = $this->getFileByPath($path);
            if (!$file) {
                throw new \Exception("File not found: {$path}");
            }

            $response = $this->service->files->get($file->getId(), ['alt' => 'media']);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $file = $this->getFileByPath($path);
            if (!$file) {
                throw new \Exception("File not found: {$path}");
            }

            $this->service->files->delete($file->getId());
        } catch (\Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $folder = $this->getFolderByPath($path);
            if (!$folder) {
                throw new \Exception("Directory not found: {$path}");
            }

            $this->service->files->delete($folder->getId());
        } catch (\Exception $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->getOrCreateParentFolder($path);
        } catch (\Exception $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Google Drive does not support visibility settings');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Google Drive does not support visibility settings');
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $file = $this->getFileByPath($path);
            if (!$file) {
                throw new \Exception("File not found: {$path}");
            }

            return new FileAttributes($path, null, null, null, $file->getMimeType());
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $file = $this->getFileByPath($path);
            if (!$file) {
                throw new \Exception("File not found: {$path}");
            }

            $timestamp = strtotime($file->getModifiedTime());
            return new FileAttributes($path, null, null, $timestamp);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $file = $this->getFileByPath($path);
            if (!$file) {
                \Log::debug('[GoogleDriveAdapter] fileSize: File not found', ['path' => $path]);
                throw new \Exception("File not found: {$path}");
            }
            $size = $file->getSize();
            \Log::debug('[GoogleDriveAdapter] fileSize', ['path' => $path, 'size' => $size, 'file' => $file]);
            return new FileAttributes($path, (int) $size);
        } catch (\Exception $e) {
            \Log::error('[GoogleDriveAdapter] fileSize error', ['path' => $path, 'error' => $e->getMessage()]);
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $folderId = $path === '' ? $this->rootFolderId : $this->getFolderByPath($path)?->getId();
            if (!$folderId) {
                return [];
            }

            $query = "'{$folderId}' in parents and trashed=false";
            $files = $this->service->files->listFiles(['q' => $query])->getFiles();

            foreach ($files as $file) {
                $filePath = $path === '' ? $file->getName() : $path . '/' . $file->getName();
                
                if ($file->getMimeType() === 'application/vnd.google-apps.folder') {
                    yield new DirectoryAttributes($filePath);
                    
                    if ($deep) {
                        yield from $this->listContents($filePath, true);
                    }
                } else {
                    yield new FileAttributes(
                        $filePath,
                        (int) $file->getSize(),
                        null,
                        strtotime($file->getModifiedTime()),
                        $file->getMimeType()
                    );
                }
            }
        } catch (\Exception $e) {
            throw UnableToListContents::atLocation($path, $deep, $e);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $file = $this->getFileByPath($source);
            if (!$file) {
                throw new \Exception("Source file not found: {$source}");
            }

            $destinationInfo = pathinfo($destination);
            $newParentId = $this->getOrCreateParentFolder($destinationInfo['dirname'] ?? '');
            
            $updatedFile = new DriveFile();
            $updatedFile->setName($destinationInfo['basename']);
            
            $this->service->files->update($file->getId(), $updatedFile, [
                'addParents' => $newParentId,
                'removeParents' => implode(',', $file->getParents())
            ]);
        } catch (\Exception $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $sourceFile = $this->getFileByPath($source);
            if (!$sourceFile) {
                throw new \Exception("Source file not found: {$source}");
            }

            $destinationInfo = pathinfo($destination);
            $parentId = $this->getOrCreateParentFolder($destinationInfo['dirname'] ?? '');
            
            $copiedFile = new DriveFile();
            $copiedFile->setName($destinationInfo['basename']);
            $copiedFile->setParents([$parentId]);

            $this->service->files->copy($sourceFile->getId(), $copiedFile);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($destination, $e->getMessage(), $e);
        }
    }

    private function getFileByPath(string $path): ?DriveFile
    {
        $pathParts = explode('/', trim($path, '/'));
        $fileName = array_pop($pathParts);
        $parentPath = implode('/', $pathParts);
        
        $parentId = $parentPath === '' ? $this->rootFolderId : $this->getFolderByPath($parentPath)?->getId();
        if (!$parentId) {
            \Log::debug('[GoogleDriveAdapter] getFileByPath: Parent folder not found', ['path' => $path, 'parentPath' => $parentPath]);
            return null;
        }

        $query = "name='{$fileName}' and '{$parentId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'";
        $files = $this->service->files->listFiles(['q' => $query])->getFiles();
        $file = $files[0] ?? null;
        \Log::debug('[GoogleDriveAdapter] getFileByPath', ['path' => $path, 'file' => $file]);
        return $file;
    }

    private function getFolderByPath(string $path): ?DriveFile
    {
        if ($path === '' || $path === '.') {
            return $this->rootFolderId ? $this->service->files->get($this->rootFolderId) : null;
        }

        $pathParts = explode('/', trim($path, '/'));
        $currentId = $this->rootFolderId;

        foreach ($pathParts as $folderName) {
            $query = "name='{$folderName}' and '{$currentId}' in parents and trashed=false and mimeType='application/vnd.google-apps.folder'";
            $folders = $this->service->files->listFiles(['q' => $query])->getFiles();
            
            if (empty($folders)) {
                return null;
            }
            
            $currentId = $folders[0]->getId();
        }

        return $this->service->files->get($currentId);
    }

    private function getOrCreateParentFolder(string $path): string
    {
        if ($path === '' || $path === '.') {
            return $this->rootFolderId ?? 'root';
        }

        $folder = $this->getFolderByPath($path);
        if ($folder) {
            return $folder->getId();
        }

        // Create the folder structure
        $pathParts = explode('/', trim($path, '/'));
        $currentId = $this->rootFolderId ?? 'root';

        foreach ($pathParts as $folderName) {
            $query = "name='{$folderName}' and '{$currentId}' in parents and trashed=false and mimeType='application/vnd.google-apps.folder'";
            $folders = $this->service->files->listFiles(['q' => $query])->getFiles();
            
            if (empty($folders)) {
                $folder = new DriveFile();
                $folder->setName($folderName);
                $folder->setMimeType('application/vnd.google-apps.folder');
                $folder->setParents([$currentId]);
                
                $createdFolder = $this->service->files->create($folder);
                $currentId = $createdFolder->getId();
            } else {
                $currentId = $folders[0]->getId();
            }
        }

        return $currentId;
    }

    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
