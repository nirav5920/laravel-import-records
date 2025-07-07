<?php

namespace Codebyray\ImportRecords\services;

use Carbon\Carbon;
use Closure;
use Codebyray\ImportRecords\Exceptions\RedirectBackWithErrorException;
use Codebyray\ImportRecords\Http\Resources\ImportRecordResource;
use Codebyray\ImportRecords\Http\Resources\ImportRecordResourceCollection;
use Codebyray\ImportRecords\Interfaces\ImportRecordClassInterface;
use Codebyray\ImportRecords\Jobs\ImportRecordsJob;
use Codebyray\ImportRecords\Queries\ImportRecordQueries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportRecordService
{
    public function processToImport(UploadedFile $file, array $metaData, ImportRecordClassInterface $importModuleFile)
    {
        $validationHeader = $this->validateUploadedFileColumns(
            $file,
            $importModuleFile
        );

        if (! $validationHeader['status']) {
            return [
                'status' => false,
                'message' => $validationHeader['message'],
            ];
        }

        DB::beginTransaction();

        $importRecordQueries = resolve(ImportRecordQueries::class);

        try {
            $importRecord = $importRecordQueries->addNew(
                $file,
                $importModuleFile->moduleTypeId,
                $metaData
            );

            DB::commit();

            ImportRecordsJob::dispatch($importRecord, $importModuleFile);

            return [
                'status' => true,
                'message' => 'Import started successfully.',
            ];

        } catch (Throwable $throwable) {
            Log::error('Import Record', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Something went wrong while starting the import. Please try again later.',
            ];
        }
    }

    public function hasMoreRecords(int $highestRow, int $rowIndex, int $totalRecords): bool
    {
        return $highestRow === $rowIndex && $rowIndex < $totalRecords;
    }

    public function headerColumnsAlreadySet(int $rowIndex, array $headerColumns): bool
    {
        return 1 === $rowIndex && count(array_filter($headerColumns));
    }

    public function getJobRestartTime(): \Illuminate\Support\Carbon
    {
        $jobExpirationTimeoutSeconds = config(
            'horizon.environments.' . config('app.env') . '.supervisor-1.timeout',
            60
        );

        return now()->addSeconds((int) $jobExpirationTimeoutSeconds * 80 / 100);
    }

    public function jobIsReadyToExpire(Carbon $jobRestartTime): bool
    {
        return now()->greaterThanOrEqualTo($jobRestartTime);
    }

    /**
     * Decides the new end row number based on the parameters passed:
     * - If current start and end row numbers are there, we just add inserted rows count to the current end row number.
     * - otherwise, we add 80% of the inserted rows count to the current end row number.
     *
     * Finally, we cross check that the total number of rows in the file before returning it.
     */
    public function getNewEndRowNumber(
        int $insertedRowsCount,
        ?int $currentEndRowNumber,
        ?int $currentStartRowNumber,
        int $totalRecordsInFile
    ): int {
        $totalRecords = $currentEndRowNumber
            ? $currentEndRowNumber - $currentStartRowNumber
            : (($insertedRowsCount - 1) * 80 / 100);

        $totalRecords = (int) $totalRecords;

        $newEndRowNumber = $insertedRowsCount + $totalRecords;

        if ($totalRecordsInFile < $newEndRowNumber) {
            return $totalRecordsInFile + 1;
        }

        return $newEndRowNumber;
    }

    public function isThisFirstImportCycle(?int $startRowNumber, ?int $endRowNumber): bool
    {
        return ! $startRowNumber && ! $endRowNumber;
    }

    public function validateUploadedFileColumns(
        UploadedFile $uploadFile,
        ImportRecordClassInterface $importModuleFile,
    ): array {
        $spreadsheet = IOFactory::load($uploadFile->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $totalRows = $worksheet->getHighestRow();

        /** @phpstan-ignore-next-line */
        $headers = array_flip(collect(current($spreadsheet->getActiveSheet()->toArray()))->filter()->toArray());

        $importFileValidationRules = $importModuleFile->validate();
        $importFileColumns = array_keys($importFileValidationRules);

        $isInvalidHeaderColumns = $this->validateColumn(
            $importFileColumns,
            $headers
        );

        if ($isInvalidHeaderColumns) {
            return [
                'status' => false,
                'message' => 'Columns do not match with the sample file.',
            ];
        }

        if ($totalRows === 1) {
            return [
                'status' => false,
                'message' => 'The uploaded file is empty.',
            ];
        }

        return [
            'status' => true,
        ];
    }

    public function validateColumn(array $requiredHeaderColumns, array $uploadHeaderColumns): bool
    {
        $missingColumns = array_diff($requiredHeaderColumns, array_keys($uploadHeaderColumns));

        return [] !== $missingColumns;
    }

    public function getImportRecordsWithPagination(int $perPage = 10, ?Closure $metaDataFilter = null, string $pageName = 'page')
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecords = $importRecordQueries->getImportRecordsWithPagination($perPage, $metaDataFilter, $pageName);

        return [
            'import_records' => ImportRecordResource::collection($importRecords)->toArray(request()),
            'meta' => [
                'total' => $importRecords->total(),
                'per_page' => $importRecords->perPage(),
                'current_page' => $importRecords->currentPage(),
                'last_page' => $importRecords->lastPage(),
            ],
        ];
    }
}
