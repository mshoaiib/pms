<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ClickUp orders tasks with a very large fractional index, such as
     * 10001789058254.00000000000000000000000000000000, which overflows an
     * integer column.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('orderindex', 30, 10)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('orderindex')->default(0)->change();
        });
    }
};
