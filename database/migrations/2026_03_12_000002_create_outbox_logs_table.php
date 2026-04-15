<?php

use App\Models\Enum\OutboxChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 20)->index();
            $table->string('event', 100)->index();
            $table->enum('preferred_channel', OutboxChannel::values())->nullable()->comment('Preferred channel for sending, null = cascade');
            $table->text('response')->nullable();
            $table->json('payload')->nullable();
            $table->json('attempts')->nullable()
                ->comment('Array: [{channel, status, reason, timestamp}, ...]');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_logs');
    }
};
