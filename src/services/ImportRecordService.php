<?php

namespace Codebyray\ImportRecords\services;

use Carbon\Carbon;
use Codebyray\ImportRecords\Exceptions\RedirectBackWithErrorException;
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
    public function processToImport(UploadedFile $file, int $typeId, int $createdById, ImportRecordClassInterface $importModuleFile)
    {
        if (!in_array($typeId, config('import-records.import_record_types'))) {
            return redirect()->back()->with('error', 'Module type is not found.');
        }

        $this->validateColumns(
            $file,
            $importModuleFile
        );

        DB::beginTransaction();

        $importRecordQueries = resolve(ImportRecordQueries::class);

        try {
            $importRecord = $importRecordQueries->addNew(
                $file,
                $typeId,
                $createdById
            );

            DB::commit();

            ImportRecordsJob::dispatch($importRecord, $importModuleFile);

            return back()
                ->with(
                    'success',
                    'File uploaded successfully. The import process will occur in the background. We will notify you by email once the import is complete.'
                );

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

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
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

    public function validateColumns(
        UploadedFile $uploadFile,
        ImportRecordClassInterface $importModuleFile,
    ): void {
        $spreadsheet = IOFactory::load($uploadFile->getPathname());
        /** @phpstan-ignore-next-line */
        $headers = array_flip(collect(current($spreadsheet->getActiveSheet()->toArray()))->filter()->toArray());

        $isInvalidHeaderColumns = $importModuleFile->validateColumns($headers);

        if ($isInvalidHeaderColumns['status']) {
            throw new RedirectBackWithErrorException('Columns do not match with the sample file.');
        }

        if (config('app.env') === 'local') {
            return;
        }

        if (! $isInvalidHeaderColumns['status']) {
            return;
        }
    }

    public function validateColumn(array $requiredHeaderColumns, array $uploadHeaderColumns): bool
    {
        $missingColumns = array_diff($requiredHeaderColumns, array_keys($uploadHeaderColumns));

        return [] !== $missingColumns;
    }
}
