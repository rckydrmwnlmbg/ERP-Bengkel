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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('type');
            $table->integer('qty_change');
            $table->unsignedBigInteger('unit_cost_snapshot');
            $table->integer('balance_after');
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['part_id', 'created_at']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT check_stock_movements_qty_change CHECK (qty_change <> 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_stock_movements_qty_insert BEFORE INSERT ON stock_movements
                WHEN NEW.qty_change = 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty_change != 0');
                END;
                CREATE TRIGGER trg_stock_movements_qty_update BEFORE UPDATE ON stock_movements
                WHEN NEW.qty_change = 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty_change != 0');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
