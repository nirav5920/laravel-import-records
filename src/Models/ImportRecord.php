<?php

namespace Codebyray\ImportRecords\Models;

use Codebyray\ImportRecords\Traits\DiskBasedFirstMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ImportRecord extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use DiskBasedFirstMediaUrl;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'type_id', 'created_by_id', 'columns', 'status', 'total_records', 'records_imported', 'records_failed'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'columns' => 'json',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('upload_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/zip',
                'application/x-zip-compressed',
            ]);

        $this->addMediaCollection('failed_rows_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/zip',
                'application/x-zip-compressed',
            ]);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(ImportRecordFailedRow::class);
    }
}
