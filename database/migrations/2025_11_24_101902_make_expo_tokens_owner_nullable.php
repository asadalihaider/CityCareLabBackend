<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expo_tokens', function (Blueprint $table) {
            $table->timestamp('last_used')->nullable()->after('value');
            $table->string('owner_type')->nullable()->change();
            $table->unsignedBigInteger('owner_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only needed if your DB already has null values
        DB::table('expo_tokens')
            ->whereNull('owner_type')
            ->update(['owner_type' => Customer::class]);

        DB::table('expo_tokens')
            ->whereNull('owner_id')
            ->update(['owner_id' => 0]);

        Schema::table('expo_tokens', function (Blueprint $table) {
            $table->dropColumn('last_used');
            $table->string('owner_type')->nullable(false)->change();
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
        });
    }
};
