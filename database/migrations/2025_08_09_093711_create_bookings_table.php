<?php

use App\Models\Enum\BookingStatus;
use App\Models\Enum\BookingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('status', BookingStatus::values())->default(BookingStatus::WAITING->value);
            $table->string('patient_name');
            $table->string('contact_number');
            $table->text('address');
            $table->enum('booking_type', BookingType::values())->default(BookingType::TEST->value);
            $table->text('purpose')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('booking_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
