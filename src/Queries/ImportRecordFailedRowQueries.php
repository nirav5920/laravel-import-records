<?php

namespace Codebyray\ImportRecords\Queries;

use Codebyray\ImportRecords\Models\ImportRecordFailedRow;

class ImportRecordFailedRowQueries
{
    public function addNew(array $recordDetails, array $validationErrors, int $importRecordId): void
    {
        ImportRecordFailedRow::create([
            'import_record_id' => $importRecordId,
            'row_data' => $recordDetails,
            'fail_reasons' => $validationErrors,
        ]);
    }

    public function deleteByImportRecordId(int $importRecordId): void
    {
        ImportRecordFailedRow::where('import_record_id', $importRecordId)->delete();
    }
}
