# Laravel Import Records

A simple package to help you import Excel files in Laravel with validation and queue processing.
This package helps you **import Excel files** into your Laravel app. It checks each row, saves the good ones, and shows you any problems.

## Notes:
- The import process runs on the `QUEUE_CONNECTION` specified in your environment configuration, and supports `redis`, `database`, and `sync` drivers.
- We use [[Laravel Media Library](https://github.com/spatie/laravel-medialibrary)] for file uploads, which works based on the `MEDIA_DISK` setting in your environment configuration. By default, files are stored on the `public` disk.

## What This Package Does

This package makes it easy to:
- Upload Excel files
- Validate the data
- Process the records in the background
- Track import status
- View successful and failed records
- Download file with failed import records

## How to Install

### Step 1: Install the package using Composer

```bash
composer require codebyray/laravel-import-records
```

### Step 2: Run migrations

```bash
php artisan migrate
```

## How to Use

### Step 1: Please run below command and create the ImportRecordTypes file
```php
php artisan import-records:generate-enum-class
```
- You can move it elsewhere if needed.


### Step 2: Please run below command and create the import user file
```php
php artisan import-records:create-stub-file-for-users-import
```

### Step 3: Process your import
In your controller or service, you can now process the import:

```php
<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Codebyray\ImportRecords\services\ImportRecordService;
use App\Domains\User\Imports\ImportUser;

class UserController extends Controller
{
    public function import(Request $request)
    {
        $importRecordService = new ImportRecordService();

        // you can pass the extra data.
        // At the fetch import record, you can filter the data based on this extra data.
        $metaData = [
            'created_by_id' => auth()->id,
        ];

        $importRecordService->processToImport(
            $request->upload_file,
            ImportRecordType::USER->value,
            $metaData,
            new ImportUser()
        );

        return back()->with('success', 'Import process started!');
    }
}
```

## Example Excel File Format

For the user import example, your Excel file should have these columns:
- name
- email
- password

## Fetch Import Records

- This service helps you retrieve paginated import records from the system, with optional metadata filtering.

### ✅ Usage
```php
$importRecordService = new ImportRecordService();

$perPage = 10;

// During the import record process, metadata can be passed to help with filtering.
// This parameter is optional. If provided, it allows you to retrieve filtered data based on the metadata.
$metaDataFilter = function ($query) {
    $query->where('meta_data->created_at_by', 2);
};

$importRecords = $importRecordService->getImportRecordsWithPagination($perPage, $metaDataFilter);
```

### Returned Fields:
- Each record includes:
    - id (Unique identifier of the import record)
    - type_id (The type or module ID)
    - meta_data (JSON metadata associated with the import)
    - status (Status of the import process)
    - total_records (Total number of rows in the import file)
    - records_imported (Number of successfully imported records)
    - records_failed (Number of failed records)
    - upload_file_url (URL to the originally uploaded file)
    - failed_records_file_url (URL to download the file containing failed records)

## Need Help?

If you need help, please create an issue on our GitHub page.

## License

This package is open-sourced software licensed under the MIT license.
