<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('measure_type', 24);
            $table->unsignedInteger('target_value')->nullable();
            $table->decimal('manual_percent', 5, 2)->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
