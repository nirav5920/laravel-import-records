<?php

namespace Codebyray\ImportRecords\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportRecordFailedRow extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['import_record_id', 'row_data', 'fail_reasons'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'row_data' => 'json',
        'fail_reasons' => 'json',
    ];
}
