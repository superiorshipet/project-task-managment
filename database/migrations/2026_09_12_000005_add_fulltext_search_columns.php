<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->text('search_text')->nullable()->after('metadata');
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('search_text')->nullable()->after('metadata');
            $table->index(['deleted_at', 'due_date']);
            $table->index(['project_id', 'deleted_at', 'status', 'due_date']);
            $table->index(['assigned_to', 'deleted_at', 'status', 'due_date']);
        });

        DB::statement('ALTER TABLE projects ADD FULLTEXT projects_search_text_fulltext (search_text)');
        DB::statement('ALTER TABLE tasks ADD FULLTEXT tasks_search_text_fulltext (search_text)');
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropFullText('tasks_search_text_fulltext');
            $table->dropIndex(['assigned_to', 'deleted_at', 'status', 'due_date']);
            $table->dropIndex(['project_id', 'deleted_at', 'status', 'due_date']);
            $table->dropIndex(['deleted_at', 'due_date']);
            $table->dropColumn('search_text');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropFullText('projects_search_text_fulltext');
            $table->dropIndex(['deleted_at', 'created_at']);
            $table->dropColumn('search_text');
        });
    }
};
