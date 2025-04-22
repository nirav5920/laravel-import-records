<?php

namespace Codebyray\ImportRecords\Jobs;

use Codebyray\ImportRecords\Queries\ImportRecordFailedRowQueries;
use Codebyray\ImportRecords\Queries\ImportRecordQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateFailedRecordsFileJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $importRecordId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecord = $importRecordQueries->getById($this->importRecordId);

        try {
            $importRecordQueries->generateFailedRecordsFile($importRecord);

            $importRecordFailedRowQueries = resolve(ImportRecordFailedRowQueries::class);
            $importRecordFailedRowQueries->deleteByImportRecordId($importRecord->id);
        } catch (Throwable $throwable) {
            Log::error('Generate Failed Records Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
