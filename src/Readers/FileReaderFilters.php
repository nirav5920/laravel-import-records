<?php

declare(strict_types=1);

namespace Codebyray\ImportRecords\Readers;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class FileReaderFilters implements IReadFilter
{
    public function __construct(
        private readonly ?int $startRow,
        private readonly ?int $endRow,
    ) {
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if (1 === $row) {
            return true;
        }

        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
