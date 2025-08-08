<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['test_id', 'test_category_id']);

            $table->index('test_id');
            $table->index('test_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_test');
    }
};
