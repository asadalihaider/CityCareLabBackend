<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('physical_card_id')->constrained('physical_cards')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_id', 'physical_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cards');
    }
};
