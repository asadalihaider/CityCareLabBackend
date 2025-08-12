<?php

use App\Models\Enum\TestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('short_title')->nullable();
            $table->string('duration');
            $table->integer('price');
            $table->string('specimen')->nullable();
            $table->enum('type', TestType::values())->default(TestType::SINGLE->value);
            $table->json('includes');
            $table->json('relevant_symptoms');
            $table->json('relevant_diseases');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
