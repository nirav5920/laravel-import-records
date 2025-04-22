<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_record_failed_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_record_id')->constrained();
            $table->json('row_data');
            $table->json('fail_reasons');
            $table->timestamps();
        });
    }
};
