<?php

declare(strict_types=1);

namespace Codebyray\ImportRecords\services;

use Codebyray\ImportRecords\Readers\FileReaderFilters;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SpreadsheetService
{
    private ?FileReaderFilters $fileReadFilters = null;

    private ?Spreadsheet $spreadsheet = null;

    public function setRowFilters(FileReaderFilters $rowFilters): self
    {
        $this->fileReadFilters = $rowFilters;

        return $this;
    }

    public function loadFileDetails(string $readerType, string $filePath): self
    {
        $reader = IOFactory::createReader($readerType);

        if ($this->fileReadFilters instanceof FileReaderFilters) {
            $reader->setReadFilter($this->fileReadFilters);
        }

        $reader->setReadEmptyCells(false)->setReadDataOnly(true);

        $this->spreadsheet = $reader->load($filePath);

        return $this;
    }

    public function getHighestRow(): int
    {
        return $this->spreadsheet instanceof Spreadsheet ? $this->spreadsheet->getActiveSheet()->getHighestDataRow() : 0;
    }

    public function getHighestColumn(): string
    {
        return $this->spreadsheet instanceof Spreadsheet ? $this->spreadsheet->getActiveSheet()->getHighestDataColumn() : '';
    }

    public function getColumnValueFor(int $rowIndex, int $columnIndex): mixed
    {
        if ($this->spreadsheet instanceof Spreadsheet) {
            return $this->spreadsheet
                ->getActiveSheet()
                ->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex)
                ->getValue();
        }

        return null;
    }

    public function columnIndexFromString(string $highestColumn): int
    {
        return Coordinate::columnIndexFromString($highestColumn);
    }

    public function createEmptyExcelFile(string $filePath): self
    {
        $this->spreadsheet = new Spreadsheet();
        $this->saveSpreadsheetToFile($filePath);

        return $this;
    }

    public function writeFromArray(
        array $data,
        string $originalFilePath,
        ?string $tempFilePath,
        string $startCell = 'A1',
    ): void {
        $worksheet = $this->spreadsheet?->getActiveSheet();
        $worksheet?->fromArray($data, null, $startCell);

        $this->saveSpreadsheetToFile($originalFilePath, $tempFilePath);
    }

    private function saveSpreadsheetToFile(string $originalFilePath, ?string $tempFilePath = null): void
    {
        if ($this->spreadsheet instanceof Spreadsheet) {
            $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $temporaryFilePath = $tempFilePath ?? sys_get_temp_dir() . '/' . basename($originalFilePath);

            $writer->save($temporaryFilePath);

            $fileContents = file_get_contents($temporaryFilePath);
            if (false !== $fileContents) {
                Storage::put($originalFilePath, $fileContents);
            }

            unlink($temporaryFilePath);
        }
    }
}
