<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('skills_needed')->nullable();
            $table->string('status', 16)->default('idea');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
