<?php
namespace Codebyray\ImportRecords;

use Codebyray\ImportRecords\Commands\MakeUserImportAssetsCommand;
use Codebyray\ImportRecords\Interfaces\ImportRecordClassInterface;
use Illuminate\Support\ServiceProvider;

class ImportRecordServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeUserImportAssetsCommand::class,
        ]);

        $this->app->bind(ImportRecordClassInterface::class, ImportRecordServiceProvider::class);
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'import-records-migrations');
        }
    }
}