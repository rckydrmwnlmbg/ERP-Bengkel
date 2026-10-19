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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE expenses ADD CONSTRAINT check_expenses_amount CHECK (amount > 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_expenses_amount_insert BEFORE INSERT ON expenses
                WHEN NEW.amount <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: amount > 0');
                END;
                CREATE TRIGGER trg_expenses_amount_update BEFORE UPDATE ON expenses
                WHEN NEW.amount <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: amount > 0');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
