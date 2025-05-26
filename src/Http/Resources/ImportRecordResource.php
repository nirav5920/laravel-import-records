<?php

namespace Codebyray\ImportRecords\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ImportRecordResource extends ResourceCollection
{
    public function toArray($request): array
    {
        return ['data' => $this->collection];
        $importRecord = $this->resource;

        return [
            'id' => $importRecord->id,
            'type_id' => $importRecord->type_id,
            'meta_data' => $importRecord->meta_data,
            'status' => $importRecord->status->name,
            'total_records' => $importRecord->total_records,
            'records_imported' => $importRecord->records_failed,
            'records_failed' => $importRecord->records_failed,
            'upload_file_url' => $importRecord->getDiskBasedFirstMediaUrl('upload_file'),
            'failed_records_file_url' => $importRecord->getDiskBasedFirstMediaUrl('failed_rows_file'),
        ];
    }
}
