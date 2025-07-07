<?php

namespace Codebyray\ImportRecords\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        $importRecord = $this->resource;

        return [
            'id' => $importRecord->id,
            'type_id' => $importRecord->type_id,
            'status' => $importRecord->status->name,
            'total_records' => $importRecord->total_records,
            'records_imported' => $importRecord->records_failed,
            'records_failed' => $importRecord->records_failed,
            'upload_file_url' => $importRecord->getDiskBasedFirstMediaUrl('upload_file'),
            'failed_records_file_url' => $importRecord->getDiskBasedFirstMediaUrl('failed_rows_file'),
        ];
    }
}
