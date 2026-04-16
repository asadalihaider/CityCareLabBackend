<?php

use App\Models\Enum\CustomerRelationship;
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
            $table->boolean('is_primary')->default(false);
            $table->enum('relationship_type', CustomerRelationship::values())
                ->default(CustomerRelationship::SELF->value);
            $table->foreignId('added_by')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();

            $table->index(['physical_card_id', 'is_primary']);
            $table->index('relationship_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cards');
    }
};
