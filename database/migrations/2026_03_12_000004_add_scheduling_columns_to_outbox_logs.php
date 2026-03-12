<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_logs', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('payload')
                ->comment('When the notification is scheduled to fire; null means immediate dispatch');
            $table->timestamp('processed_at')->nullable()->after('scheduled_at')
                ->comment('When the job actually executed (set by OutboxService on completion)');
        });
    }

    public function down(): void
    {
        Schema::table('outbox_logs', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'processed_at']);
        });
    }
};
