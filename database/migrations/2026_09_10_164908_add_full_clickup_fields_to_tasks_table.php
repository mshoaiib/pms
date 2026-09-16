<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror every field of the ClickUp task object.
     *
     * The `list`, `project`, `folder` and `space` keys are left out: they only
     * repeat the parents this table already reaches through `task_list_id`.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('custom_item_id')->nullable()->after('custom_id');
            $table->text('text_content')->nullable()->after('description');

            $table->string('status_id')->nullable()->after('status');
            $table->string('status_type')->nullable()->after('status_color');

            $table->string('priority_label')->nullable()->after('priority');
            $table->string('priority_color')->nullable()->after('priority_label');

            $table->foreignId('parent_id')
                ->nullable()
                ->after('task_list_id')
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->string('parent_tid')->nullable()->after('parent_id')->index();
            $table->string('top_level_parent_tid')->nullable()->after('parent_tid');

            $table->timestamp('clickup_created_at')->nullable()->after('start_date');
            $table->timestamp('clickup_updated_at')->nullable()->after('clickup_created_at');
            $table->timestamp('closed_at')->nullable()->after('clickup_updated_at');
            $table->timestamp('done_at')->nullable()->after('closed_at');

            $table->decimal('points', 12, 2)->nullable()->after('time_estimate');
            $table->unsignedBigInteger('time_spent')->nullable()->after('points');

            $table->json('creator')->nullable()->after('assignees');
            $table->json('group_assignees')->nullable()->after('creator');
            $table->json('watchers')->nullable()->after('group_assignees');
            $table->json('checklists')->nullable()->after('tags');
            $table->json('custom_fields')->nullable()->after('checklists');
            $table->json('dependencies')->nullable()->after('custom_fields');
            $table->json('linked_tasks')->nullable()->after('dependencies');
            $table->json('locations')->nullable()->after('linked_tasks');
            $table->json('attachments')->nullable()->after('locations');
            $table->json('sharing')->nullable()->after('attachments');

            $table->string('team_id')->nullable()->after('url');
            $table->string('permission_level')->nullable()->after('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');

            $table->dropColumn([
                'custom_item_id',
                'text_content',
                'status_id',
                'status_type',
                'priority_label',
                'priority_color',
                'parent_tid',
                'top_level_parent_tid',
                'clickup_created_at',
                'clickup_updated_at',
                'closed_at',
                'done_at',
                'points',
                'time_spent',
                'creator',
                'group_assignees',
                'watchers',
                'checklists',
                'custom_fields',
                'dependencies',
                'linked_tasks',
                'locations',
                'attachments',
                'sharing',
                'team_id',
                'permission_level',
            ]);
        });
    }
};
