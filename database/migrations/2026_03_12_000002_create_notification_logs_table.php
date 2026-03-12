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
            $table->string('channel', 20); // expo | whatsapp | sms
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending'); // pending | sent | failed | skipped
            $table->text('response')->nullable()->comment('Raw response from the channel provider');
            $table->json('payload')->nullable()->comment('Original data payload passed with the event');
            $table->timestamps();

            $table->index(['status', 'channel']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_logs');
    }
};
