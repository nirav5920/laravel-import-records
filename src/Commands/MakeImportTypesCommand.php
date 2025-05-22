<?php

namespace Codebyray\ImportRecords\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeUserImportAssetsCommand extends Command
{
    protected $signature = 'import-records:generate-enum-class';
    protected $description = 'Generate ImportRecordType enum';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $file = [
            'stub' => '/../../stubs/import-record-type-enum.stub',
            'target' => app_path('Enums/ImportRecordTypes.php'),
            'namespace' => 'App\\Enums',
        ];


        if ($filesystem->exists($file['target'])) {
            $this->warn(basename($file['target']) . ' already exists.');
            return;
        }

        $stubContent = $filesystem->get(__DIR__ . $file['stub']);

        $filesystem->ensureDirectoryExists(dirname($file['target']));
        $filesystem->put($file['target'], $stubContent);

        $this->info(basename($file['target']) . ' created successfully.');
    }
}
