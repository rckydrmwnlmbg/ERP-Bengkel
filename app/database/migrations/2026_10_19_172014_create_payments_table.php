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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->date('paid_at');
            $table->unsignedBigInteger('amount');
            $table->enum('method', ['cash', 'transfer', 'other']);
            $table->string('reference')->nullable();
            $table->enum('status', ['valid', 'void'])->default('valid');
            $table->text('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('paid_at');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT check_payments_amount CHECK (amount > 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_payments_amount_insert BEFORE INSERT ON payments
                WHEN NEW.amount <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: amount > 0');
                END;
                CREATE TRIGGER trg_payments_amount_update BEFORE UPDATE ON payments
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
        Schema::dropIfExists('payments');
    }
};
