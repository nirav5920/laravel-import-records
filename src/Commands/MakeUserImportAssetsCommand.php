<?php

namespace Codebyray\ImportRecords\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeUserImportAssetsCommand extends Command
{
    protected $signature = 'import-records:make-user-import-assets';
    protected $description = 'Generate UserController, ImportUser class, and ImportRecordType enum';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $filesToPublish = [
            [
                'stub' => '/../../stubs/user-controller.stub',
                'target' => app_path('Http/Controllers/UserController.php'),
                'namespace' => 'App\\Http\\Controllers',
            ],
            [
                'stub' => '/../../stubs/import-user.stub',
                'target' => app_path('Domains/User/Imports/ImportUser.php'),
                'namespace' => 'App\\Domains\\User\\Imports',
            ],
            [
                'stub' => '/../../stubs/import-record-type-enum.stub',
                'target' => app_path('Domains/ImportRecord/Enums/ImportRecordType.php'),
                'namespace' => 'App\\Domains\\ImportRecord\\Enums',
            ],
        ];

        foreach ($filesToPublish as $file) {
            if ($filesystem->exists($file['target'])) {
                $this->warn(basename($file['target']) . ' already exists. Skipping...');
                continue;
            }

            $stubContent = $filesystem->get(__DIR__ . $file['stub']);
            $stubContent = str_replace('{{ namespace }}', $file['namespace'], $stubContent);

            $filesystem->ensureDirectoryExists(dirname($file['target']));
            $filesystem->put($file['target'], $stubContent);

            $this->info(basename($file['target']) . ' created successfully.');
        }
    }
}
