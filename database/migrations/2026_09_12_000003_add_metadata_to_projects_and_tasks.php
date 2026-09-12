<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('status');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('progress');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
