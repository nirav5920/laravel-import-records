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

### Step 1: Set your module value in import-records.php (Config File)

```php

use App\Domains\ImportRecord\Enums\ImportRecordType;

return [
    'import_record_types' => [
        ImportRecordType::USER->value
    ],
];
```

### Step 2: Create an Enum for your import columns

Create a file for your import columns. For example, if you're importing users:

```php
<?php

namespace App\Domains\User\Enums;

enum UserImportColumns: int
{
    case NAME = 'name';
    case EMAIL = 'email';
    case REFERRER_PARTNER_USER_ID = 'referrer_partner_user_id';
}
```

### Step 3: Create an Enum for validation issue types

```php
<?php

namespace App\Domains\ImportRecord\Enums;

enum ColumnValidationIssueTypes: int
{
    case COLUMN_ISSUE = 1;
}
```

### Step 4: Create your Import class

Create a class that implements `ImportRecordClassInterface`. This class will handle validation and saving of your import data:

```php
<?php

namespace App\Domains\User\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\User\Enums\UserImportColumns;
use Codebyray\ImportRecords\Interfaces\ImportRecordClassInterface;
use Codebyray\ImportRecords\Models\ImportRecord;
use Codebyray\ImportRecords\services\ImportRecordService;

class ImportUser implements ImportRecordClassInterface
{
    // Validate each row of data
    public function validate(array $userDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        
        // Add your validation rules here
        if (!array_key_exists('name', $userDetails) || !$userDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }
        
        if (!array_key_exists('email', $userDetails) || !$userDetails['email']) {
            $validationErrors[] = 'The email is required.';
        }
        
        return $validationErrors;
    }

    // Save each valid row of data
    public function save(array $userDetails, ImportRecord $importRecord): void
    {
        // Your code to save the record
        // For example:
        // User::create([
        //     'name' => $userDetails['name'],
        //     'email' => $userDetails['email'],
        // ]);
    }

    // Validate the column headers in your Excel file
    public function validateColumns(array $uploadHeaderColumns): array
    {
        $requiredHeaderColumns = collect(UserImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
```

### Step 5: Process your import

In your controller or service, you can now process the import:

```php
use Codebyray\ImportRecords\services\ImportRecordService;
use App\Domains\User\Imports\ImportUser;

// In your controller method
public function import(Request $request)
{
    $importRecordService = new ImportRecordService();
    
    // Parameters:
    // 1. The Excel file from the request
    // 2. The type ID of the import (define this in your config)
    // 3. The user ID performing the import
    // 4. Your import class instance
    $importRecordService->processToImport(
        $request->upload_file, 
        $request->type_id, 
        auth()->id(), 
        new ImportUser()
    );
    
    return back()->with('success', 'Import started! Check back later for results.');
}
```

## Example Excel File Format

For the user import example, your Excel file should have these columns:
- name
- email
- referrer_partner_user_id

Make sure your Excel file has all the required columns with the exact same names.

## Need Help?

If you need help, please create an issue on our GitHub page.

## License

This package is open-sourced software licensed under the MIT license.
