<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue details a wholesale distributor keeps per product: its own item
 * code, the brand, how it is packed, what one unit weighs and how many
 * pieces go in a carton. All optional, so existing items are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->string('brand')->nullable()->after('sku');
            $table->string('packaging')->nullable()->after('brand');
            $table->decimal('weight', 12, 3)->nullable()->after('packaging');
            $table->string('weight_unit', 8)->nullable()->after('weight');
            $table->unsignedInteger('pieces_per_carton')->nullable()->after('weight_unit');

            $table->index(['company_id', 'sku']);
            $table->index(['company_id', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'sku']);
            $table->dropIndex(['company_id', 'brand']);
            $table->dropColumn(['sku', 'brand', 'packaging', 'weight', 'weight_unit', 'pieces_per_carton']);
        });
    }
};
