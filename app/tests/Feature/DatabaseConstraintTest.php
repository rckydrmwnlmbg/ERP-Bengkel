<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Constraint: parts.qty_on_hand >= 0
     */
    public function test_parts_qty_on_hand_cannot_be_negative(): void
    {
        $this->expectException(QueryException::class);

        DB::table('parts')->insert([
            'sku' => 'PART-TEST-001',
            'name' => 'Test Part Negative',
            'category' => 'Filter',
            'unit' => 'pcs',
            'sell_price' => 100000,
            'avg_cost' => 80000,
            'qty_on_hand' => -1,
            'min_stock' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Constraint: purchase_items.qty > 0
     */
    public function test_purchase_items_qty_must_be_greater_than_zero(): void
    {
        $supplierId = DB::table('suppliers')->insertGetId([
            'name' => 'Supplier Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = DB::table('purchases')->insertGetId([
            'supplier_id' => $supplierId,
            'purchase_date' => '2026-10-19',
            'total' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partId = DB::table('parts')->insertGetId([
            'sku' => 'PART-TEST-002',
            'name' => 'Test Part Pos',
            'category' => 'Filter',
            'unit' => 'pcs',
            'sell_price' => 100000,
            'avg_cost' => 80000,
            'qty_on_hand' => 10,
            'min_stock' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'part_id' => $partId,
            'qty' => 0,
            'unit_cost' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Constraint: stock_movements.qty_change != 0
     */
    public function test_stock_movements_qty_change_cannot_be_zero(): void
    {
        $partId = DB::table('parts')->insertGetId([
            'sku' => 'PART-TEST-003',
            'name' => 'Test Part SM',
            'category' => 'Filter',
            'unit' => 'pcs',
            'sell_price' => 100000,
            'avg_cost' => 80000,
            'qty_on_hand' => 10,
            'min_stock' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('stock_movements')->insert([
            'part_id' => $partId,
            'type' => 'PURCHASE_IN',
            'qty_change' => 0,
            'unit_cost_snapshot' => 80000,
            'balance_after' => 10,
            'created_at' => now(),
        ]);
    }

    /**
     * Constraint: payments.amount > 0
     */
    public function test_payments_amount_must_be_greater_than_zero(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'code' => 'CUST-TEST-001',
            'name' => 'Customer Test',
            'type' => 'company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $customerId,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-19',
            'due_date' => '2026-10-19',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 100000,
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('payments')->insert([
            'invoice_id' => $invoiceId,
            'paid_at' => '2026-10-19',
            'amount' => 0,
            'method' => 'cash',
            'status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Constraint: expenses.amount > 0
     */
    public function test_expenses_amount_must_be_greater_than_zero(): void
    {
        $this->expectException(QueryException::class);

        DB::table('expenses')->insert([
            'expense_date' => '2026-10-19',
            'category' => 'Operasional',
            'amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Constraint: Unique codes and keys
     */
    public function test_unique_constraints_are_enforced(): void
    {
        DB::table('customers')->insert([
            'code' => 'CUST-DUP-01',
            'name' => 'Customer 1',
            'type' => 'individual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('customers')->insert([
            'code' => 'CUST-DUP-01',
            'name' => 'Customer 2',
            'type' => 'individual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
