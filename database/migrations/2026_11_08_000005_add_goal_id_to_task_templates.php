<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->foreignId('goal_id')->nullable()->after('alarm_enabled')->constrained()->nullOnDelete();
            $table->index(['user_id', 'goal_id']);
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropForeign(['goal_id']);
            $table->dropIndex(['user_id', 'goal_id']);
            $table->dropColumn('goal_id');
        });
    }
};
