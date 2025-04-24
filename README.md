# Laravel Import Records

A simple package to help you import Excel files in Laravel with validation and queue processing.
This package helps you **import Excel files** into your Laravel app. It checks each row, saves the good ones, and shows you any problems.

## What This Package Does

This package makes it easy to:
- Upload Excel files
- Validate the data
- Process the records in the background
- Track import status
- View successful and failed records
- Download error reports

## How to Install

### Step 1: Install the package using Composer

```bash
composer require codebyray/laravel-import-records
```

### Step 2: Publish the config and migration files

```bash
php artisan vendor:publish --provider="Codebyray\ImportRecords\ImportRecordServiceProvider"
```

### Step 3: Run migrations

```bash
php artisan migrate
```

## How to Use

### Please run the below command in your application
- This command automatically generates all the required files for the import record process.

```php
php artisan import-records:make-user-import-assets
```
### OR you can follow the manual process in your application

### Step 1: Create your Import Type Enum

```php
<?php

namespace {{ namespace }};

enum ImportRecordType: int
{
    case USER = 1;
    case PRODUCT = 2; // Like this
    // You can add the others module.
}
```


### Step 2: Create your Import class

Create a class that implements `ImportRecordClassInterface`. This class will handle validation and saving of your import data:

```php
<?php

namespace App\Domains\User\Imports;

use App\Domains\User\Enums\UserImportColumns;
use App\Domains\User\Queries\UserQueries;
use Codebyray\ImportRecords\Interfaces\ImportRecordClassInterface;
use Codebyray\ImportRecords\Models\ImportRecord;
use Codebyray\ImportRecords\services\ImportRecordService;
use Illuminate\Support\Facades\Hash;

class ImportUser implements ImportRecordClassInterface
{
    public function validate(array $userDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $userQueries = resolve(UserQueries::class);

        if (! array_key_exists('name', $userDetails) || ! $userDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }

        if (! array_key_exists('email', $userDetails) || ! $userDetails['email']) {
            $validationErrors[] = 'The email is required.';
        } elseif ($userQueries->existsByEmail((string) $userDetails['email'])) {
            $validationErrors[] = 'The specified email is already available in our records.';
        }

        if (! array_key_exists('password', $userDetails) || ! $userDetails['password']) {
            $validationErrors[] = 'The password is required.';
        }

        return $validationErrors;
    }

    public function save(array $userDetails, ImportRecord $importRecord): void
    {
        User::create([
            'name' => $userDetails['name'],
            'email' => $userDetails['email'],
            'password' => Hash::make($userDetails['password']),
        ]);
    }

    public function validateColumns(array $uploadHeaderColumns): array
    {
        $requiredHeaderColumns = $this->getColumns();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    public function getColumns(): array
    {
        // Make sure your Excel file has all the required columns with the exact same names.
        return [
            'name',
            'email',
            'password',
        ];
    }
}
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
