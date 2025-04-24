<?php

namespace Codebyray\ImportRecords\Interfaces;

use Codebyray\ImportRecords\Models\ImportRecord;

interface ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $recordDetails, ImportRecord $importRecord): array;

    public function validateColumns(array $uploadHeaderColumns): array;

    public function save(array $recordDetails, ImportRecord $importRecord): void;

    public function getColumns(): array;
}
