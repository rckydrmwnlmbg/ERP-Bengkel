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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('unit');
            $table->unsignedBigInteger('sell_price')->default(0);
            $table->unsignedBigInteger('avg_cost')->default(0);
            $table->integer('qty_on_hand')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE parts ADD CONSTRAINT check_parts_qty_on_hand CHECK (qty_on_hand >= 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_parts_qty_insert BEFORE INSERT ON parts
                WHEN NEW.qty_on_hand < 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty_on_hand >= 0');
                END;
                CREATE TRIGGER trg_parts_qty_update BEFORE UPDATE ON parts
                WHEN NEW.qty_on_hand < 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: qty_on_hand >= 0');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
