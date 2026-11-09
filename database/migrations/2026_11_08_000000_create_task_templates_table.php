<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 24);
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->json('weekdays');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('remind_minutes_before')->default(5);
            $table->boolean('alarm_enabled')->default(false);
            $table->string('minimum_version')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_active', 'sort_order']);
        });

        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_template_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('scheduled_at');
            $table->string('status', 16)->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedSmallInteger('minutes_spent')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['task_template_id', 'date']);
            $table->index(['user_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_logs');
        Schema::dropIfExists('task_templates');
    }
};
