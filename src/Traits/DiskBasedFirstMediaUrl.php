<?php

declare(strict_types=1);

namespace Codebyray\ImportRecords\Traits;

use Codebyray\ImportRecords\Enums\StorageTypes;
use Codebyray\ImportRecords\services\StorageService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait DiskBasedFirstMediaUrl
{
    public function getDiskBasedFirstMediaUrl(string $collectionName): string
    {
        if (! method_exists($this, 'getFirstMedia')) {
            throw new Exception('The class does not have media setup.');
        }

        $media = $this->getFirstMedia($collectionName);

        if (! $media) {
            return '';
        }

        if (StorageTypes::S3->value === $media->disk) {
            return $media->getTemporaryUrl(now()->addMinutes(5));
        }

        return $media->getUrl();
    }

    public function getDiskBasedFirstMedia(string $collectionName): ?Media
    {
        if (! method_exists($this, 'getFirstMedia')) {
            throw new Exception('The class does not have media setup.');
        }

        return $this->getFirstMedia($collectionName);
    }

    public function getLocalFilePath(string $collectionName): string
    {
        $media = $this->getDiskBasedFirstMedia($collectionName);

        if (! $media) {
            return '';
        }

        if (StorageTypes::S3->value === $media->disk) {
            $fullFilePath = $this->saveFileToLocalStorage($collectionName);

            return Storage::disk(StorageTypes::PUBLIC->value)->path($fullFilePath);
        }

        return $media->getPath();
    }

    private function saveFileToLocalStorage(string $collectionName): string
    {
        $fileUrl = $this->getDiskBasedFirstMediaUrl($collectionName);
        $storageService = resolve(StorageService::class);

        return $storageService->saveFileToLocalStorage($fileUrl);
    }
}
