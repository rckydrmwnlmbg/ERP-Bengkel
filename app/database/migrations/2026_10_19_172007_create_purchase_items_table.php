<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->integer('qty');
            $table->unsignedBigInteger('unit_cost');
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT check_purchase_items_qty CHECK (qty > 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_purchase_items_qty_insert BEFORE INSERT ON purchase_items
                WHEN NEW.qty <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty > 0');
                END;
                CREATE TRIGGER trg_purchase_items_qty_update BEFORE UPDATE ON purchase_items
                WHEN NEW.qty <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty > 0');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
