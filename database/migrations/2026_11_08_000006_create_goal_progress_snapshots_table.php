<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('percent', 5, 2);
            $table->timestamps();
            $table->unique(['goal_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress_snapshots');
    }
};
