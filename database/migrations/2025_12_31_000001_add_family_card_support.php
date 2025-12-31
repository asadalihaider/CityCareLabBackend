<?php

use App\Models\Enum\CustomerRelationship;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_cards', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_members')->default(1)->after('price');
        });

        Schema::table('customer_cards', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'physical_card_id']);

            $table->boolean('is_primary')->default(false)->after('physical_card_id');
                $table->enum('relationship_type', CustomerRelationship::values())
                    ->default(CustomerRelationship::SELF->value)
                    ->after('is_primary');
            $table->foreignId('added_by')->nullable()->after('relationship_type')->constrained('customers')->nullOnDelete();
            
            $table->index(['physical_card_id', 'is_primary']);
            $table->index('relationship_type');
        });
    }

    public function down(): void
    {
        Schema::table('customer_cards', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn(['is_primary', 'relationship_type', 'added_by']);

            $table->unique(['customer_id', 'physical_card_id']);
        });

        Schema::table('health_cards', function (Blueprint $table) {
            $table->dropColumn('max_members');
        });
    }
};
