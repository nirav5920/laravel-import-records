<?php

namespace Codebyray\ImportRecords\Queries;

use Closure;
use Codebyray\ImportRecords\Enums\Status;
use Codebyray\ImportRecords\Exports\ImportRecordFailedRowExport;
use Codebyray\ImportRecords\Models\ImportRecord;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportRecordQueries
{
    public function addNew(UploadedFile $file, int $typeId, array $metaData)
    {
        $importRecord = ImportRecord::create([
            'type_id' => $typeId,
            'meta_data' => $metaData,
        ]);

        $this->uploadFile($importRecord, $file);

        return $importRecord;
    }

    public function getUploadedMedia(ImportRecord $importRecord): Media
    {
        return $importRecord->getDiskBasedFirstMedia('upload_file');
    }

    public function getFilePath(ImportRecord $importRecord): string
    {
        return $importRecord->getLocalFilePath('upload_file');
    }

    public function saveHeaderColumns(ImportRecord $importRecord, array $headerColumns): void
    {
        $importRecord->columns = $headerColumns;
        $importRecord->save();
    }

    public function markAsInProgress(ImportRecord $importRecord, int $totalRows): void
    {
        $importRecord->total_records = $totalRows;
        $importRecord->status = Status::IN_PROGRESS->value;
        $importRecord->save();
    }

    public function incrementImportedRecordsCount(ImportRecord $importRecord): void
    {
        $importRecord->records_imported += 1;
        $importRecord->save();
    }

    public function incrementFailedRecordsCount(ImportRecord $importRecord): void
    {
        $importRecord->records_failed += 1;
        $importRecord->save();
    }

    public function markAsCompleted(ImportRecord $importRecord): void
    {
        $importRecord->status = Status::COMPLETED->value;
        $importRecord->save();
    }

    public function getById(int $id): ImportRecord
    {
        return ImportRecord::query()
            ->findOrFail($id);
    }

    public function generateFailedRecordsFile(ImportRecord $importRecord): void
    {
        if (! $importRecord->records_failed) {
            return;
        }

        $filename = now()->format('y-m-d h-i-s') . '.xlsx';

        $binaryFileResponse = Excel::download(
            new ImportRecordFailedRowExport($importRecord, $importRecord->columns ?: []),
            $filename
        );

        $filePath = $binaryFileResponse->getFile()->getPathname();

        $importRecord->addMedia($filePath)
            ->setFileName($filename)
            ->toMediaCollection('failed_rows_file');
    }

    public function getImportRecordsWithPagination(int $perPage = 10, ?Closure $metaDataFilter = null, string $pageName = 'page')
    {
        $query = ImportRecord::select('id', 'type_id', 'meta_data', 'status', 'total_records', 'records_imported', 'records_failed', 'created_at')
            ->with('media');

        if ($metaDataFilter !== null) {
            $metaDataFilter($query);
        }

        return $query->paginate(
            perPage: $perPage,
            pageName: $pageName
        );
    }

    private function uploadFile(ImportRecord $importRecord, UploadedFile $file): void
    {
        $importRecord->addMedia($file)
            ->toMediaCollection('upload_file');
    }
}