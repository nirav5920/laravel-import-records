<?php

namespace Codebyray\ImportRecords\Exports;

use Codebyray\ImportRecords\Models\ImportRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportRecordFailedRowExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected ImportRecord $importRecord,
        private readonly array $headings,
    ) {
        $this->importRecord->load('failedRows');
    }

    public function collection(): Collection
    {
        return $this->importRecord->failedRows->map(function ($failedRow): array {
            $failedRowDetails = $failedRow->row_data;
            $rowData = [];

            foreach ($this->headings as $heading) {
                $rowData[$heading] = $failedRowDetails[$heading];
            }

            $rowData['failed_reasons'] = collect($failedRow->fail_reasons)->implode(', ');

            return $rowData;
        });
    }

    /**
     * @return mixed[]
     */
    public function headings(): array
    {
        return [...$this->headings, 'Failed Reasons'];
    }
}
