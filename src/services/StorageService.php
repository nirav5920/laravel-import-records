<?php

declare(strict_types=1);

namespace Codebyray\ImportRecords\services;

use Codebyray\ImportRecords\Enums\StorageTypes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function saveFileToLocalStorage(string $fileUrl): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->get($fileUrl);

        /** @var string $basePath */
        $basePath = parse_url($fileUrl, PHP_URL_PATH);
        $filename = Str::random(20) . '-' . basename($basePath);
        $fullFilePath = 'temporary_files/' . $filename;

        Storage::disk(StorageTypes::PUBLIC->value)->put($fullFilePath, $response->getBody());

        return $fullFilePath;
    }
}
