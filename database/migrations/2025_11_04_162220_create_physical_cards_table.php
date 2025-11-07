<?php

use App\Models\Enum\PhysicalCardStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_card_id')->constrained('health_cards')->onDelete('cascade');
            $table->string('serial_number', 50)->unique();
            $table->date('expiry_date');
            $table->enum('status', PhysicalCardStatus::values())->default(PhysicalCardStatus::AVAILABLE->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['serial_number', 'expiry_date']);
            $table->index(['status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_cards');
    }
};
