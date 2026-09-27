<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a document line was sold by the carton or by the piece, and how
 * many pieces made a carton at the time. The pieces count is a snapshot of
 * the catalogue item, so later edits to the item leave issued documents as
 * they were. Both nullable: lines for items without a carton size have none.
 */
return new class extends Migration
{
    private const TABLES = ['invoice_items', 'estimate_items'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('sale_unit', 16)->nullable()->after('quantity');
                $table->unsignedInteger('pieces_per_carton')->nullable()->after('sale_unit');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['sale_unit', 'pieces_per_carton']);
            });
        }
    }
};
