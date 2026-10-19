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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('source_type', ['work_order', 'counter_sale']);
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total');
            $table->enum('status', ['issued', 'partially_paid', 'paid', 'void'])->default('issued');
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT check_invoices_total CHECK (total > 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER trg_invoices_total_insert BEFORE INSERT ON invoices
                WHEN NEW.total <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: total > 0');
                END;
                CREATE TRIGGER trg_invoices_total_update BEFORE UPDATE ON invoices
                WHEN NEW.total <= 0
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: total > 0');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
