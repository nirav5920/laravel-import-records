<?php

namespace Codebyray\ImportRecords\Interfaces;

use Codebyray\ImportRecords\Models\ImportRecord;

interface ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(): array;

    public function save(array $recordDetails, ImportRecord $importRecord): void;
}
