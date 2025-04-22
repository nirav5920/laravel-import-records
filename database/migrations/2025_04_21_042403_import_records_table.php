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
        Schema::create('import_records', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type_id');
            $table->bigInteger('created_by_id');
            $table->json('columns')->nullable();
            $table->tinyInteger('status')->default(1)->comment("1 = Pending, 2 = in progress, 3 = completed");
            $table->integer('total_records')->default(0);
            $table->integer('records_imported')->default(0);
            $table->integer('records_failed')->default(0);
            $table->timestamps();
        });
    }
};
