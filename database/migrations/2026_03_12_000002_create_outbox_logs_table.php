<?php

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
            $table->string('title')->nullable();
            $table->text('body')->nullable();
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
