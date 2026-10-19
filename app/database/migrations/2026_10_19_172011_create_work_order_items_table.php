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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->enum('item_type', ['part', 'service']);
            $table->foreignId('part_id')->nullable()->constrained('parts')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('description')->nullable();
            $table->integer('qty');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('unit_cost_snapshot')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE work_order_items ADD CONSTRAINT check_wo_items_qty CHECK (qty > 0)');
            DB::statement("ALTER TABLE work_order_items ADD CONSTRAINT check_wo_items_type CHECK ((item_type = 'part' AND part_id IS NOT NULL AND service_id IS NULL) OR (item_type = 'service' AND service_id IS NOT NULL AND part_id IS NULL))");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_wo_items_insert BEFORE INSERT ON work_order_items
                WHEN NEW.qty <= 0 OR NOT ((NEW.item_type = 'part' AND NEW.part_id IS NOT NULL AND NEW.service_id IS NULL) OR (NEW.item_type = 'service' AND NEW.service_id IS NOT NULL AND NEW.part_id IS NULL))
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: work_order_items validation');
                END;
                CREATE TRIGGER trg_wo_items_update BEFORE UPDATE ON work_order_items
                WHEN NEW.qty <= 0 OR NOT ((NEW.item_type = 'part' AND NEW.part_id IS NOT NULL AND NEW.service_id IS NULL) OR (NEW.item_type = 'service' AND NEW.service_id IS NOT NULL AND NEW.part_id IS NULL))
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: work_order_items validation');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
