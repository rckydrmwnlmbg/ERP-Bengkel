<?php

namespace Tests\Feature\Report;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);
    }

    /**
     * RBAC Pengeluaran: Admin berhak (200), Kasir ditolak (403), Mekanik ditolak (403).
     */
    public function test_rbac_expenses_access(): void
    {
        $this->actingAs($this->admin)->get(route('expenses.index'))->assertStatus(200);
        $this->actingAs($this->cashier)->get(route('expenses.index'))->assertStatus(403);
        $this->actingAs($this->mechanic)->get(route('expenses.index'))->assertStatus(403);
    }

    /**
     * Admin dapat membuat pengeluaran baru dan tercatat di audit log.
     */
    public function test_admin_can_create_expense_with_audit_log(): void
    {
        $response = $this->actingAs($this->admin)->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(),
            'category' => 'Listrik & Air',
            'amount' => 150000,
            'description' => 'Token PLN bengkel',
        ]);

        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'category' => 'Listrik & Air',
            'amount' => 150000,
            'description' => 'Token PLN bengkel',
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense_created',
            'entity_type' => 'Expense',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Validasi: Nominal pengeluaran <= 0 harus ditolak.
     */
    public function test_expense_amount_must_be_greater_than_zero(): void
    {
        $response = $this->actingAs($this->admin)->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(),
            'category' => 'Listrik & Air',
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * Admin dapat memperbarui dan menghapus pengeluaran operasional.
     */
    public function test_admin_can_update_and_delete_expense(): void
    {
        $expense = Expense::create([
            'expense_date' => now()->toDateString(),
            'category' => 'Sewa Tempat',
            'amount' => 2000000,
            'description' => 'Sewa ruko workshop',
            'created_by' => $this->admin->id,
        ]);

        // Update
        $responseUpdate = $this->actingAs($this->admin)->put(route('expenses.update', $expense), [
            'expense_date' => now()->toDateString(),
            'category' => 'Sewa Tempat',
            'amount' => 2500000,
            'description' => 'Sewa ruko + gudang',
        ]);

        $responseUpdate->assertRedirect(route('expenses.index'));
        $this->assertEquals(2500000, $expense->fresh()->amount);

        // Delete
        $responseDelete = $this->actingAs($this->admin)->delete(route('expenses.destroy', $expense));
        $responseDelete->assertRedirect(route('expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
}
