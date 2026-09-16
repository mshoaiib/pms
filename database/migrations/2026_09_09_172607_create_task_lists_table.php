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
        Schema::create('task_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('folder_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('lid');
            $table->string('name');
            $table->text('content')->nullable();
            $table->integer('orderindex')->default(0);
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->boolean('archived')->default(false);
            $table->unsignedInteger('task_count')->default(0);
            $table->timestamp('due_date')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamps();

            $table->unique(['space_id', 'lid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_lists');
    }
};
