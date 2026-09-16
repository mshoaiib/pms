<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('fid');
            $table->string('name');
            $table->integer('orderindex')->default(0);
            $table->boolean('override_statuses')->default(false);
            $table->boolean('hidden')->default(false);
            $table->boolean('archived')->default(false);
            $table->unsignedInteger('task_count')->default(0);
            $table->json('statuses')->nullable();
            $table->timestamps();

            $table->unique(['space_id', 'fid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
