<?php

use App\Models\Enum\FeedbackCategory;
use App\Models\Enum\FeedbackStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->text('message');
            $table->enum('rating', ['1', '2', '3', '4', '5'])->nullable();
            $table->enum('category', FeedbackCategory::values())->default(FeedbackCategory::GENERAL->value);
            $table->enum('status', FeedbackStatus::values())->default(FeedbackStatus::DRAFT->value);
            $table->boolean('is_anonymous')->default(false);
            $table->string('contact_email')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
