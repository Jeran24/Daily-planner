<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', PushSubscription::ENDPOINT_MAX_LENGTH)->charset('ascii')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->string('device_label', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('reminder_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_log_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['task_log_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_events');
        Schema::dropIfExists('push_subscriptions');
    }
};
