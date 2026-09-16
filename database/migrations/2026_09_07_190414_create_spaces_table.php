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
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('sid');
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('private')->default(true);
            $table->json('statuses')->nullable();
            $table->boolean('multiple_assignees')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
        });
        Schema::create('user_space', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('space_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'space_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_space');
        Schema::dropIfExists('spaces');
    }
};
