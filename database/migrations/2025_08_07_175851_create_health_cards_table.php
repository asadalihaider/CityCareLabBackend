<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('features')->nullable();
            $table->string('link')->nullable();
            $table->string('serial_prefix', 10)->default('CARD');
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_cards');
    }
};
