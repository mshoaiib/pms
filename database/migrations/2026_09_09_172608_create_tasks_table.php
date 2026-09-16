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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_list_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('tid');
            $table->string('custom_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('status_color')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->integer('orderindex')->default(0);
            $table->boolean('archived')->default(false);
            $table->json('assignees')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->unsignedBigInteger('time_estimate')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->unique(['task_list_id', 'tid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
