<?php

namespace Codebyray\ImportRecords\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeUserImportAssetsCommand extends Command
{
    protected $signature = 'create-stub-file-for-users-import';
    protected $description = 'Generate UserController, ImportUser class';

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
