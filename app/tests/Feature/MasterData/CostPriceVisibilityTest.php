<?php

namespace Tests\Feature\MasterData;

use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostPriceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;
    protected Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cashier = User::factory()->cashier()->create();
        $this->mechanic = User::factory()->mechanic()->create();

        $this->part = Part::create([
            'sku' => 'SP-SEC-001',
            'name' => 'Hydraulic Pump Main Seal',
            'category' => 'Hidrolik',
            'unit' => 'Set',
            'sell_price' => 750000,
            'avg_cost' => 487500, // Nilai rahasia HPP / Avg Cost
            'qty_on_hand' => 10,
            'min_stock' => 2,
        ]);
    }

    public function test_admin_sees_cost_price_on_parts_index_and_show(): void
    {
        // Admin melihat HPP di index
        $this->actingAs($this->admin)->get('/parts')
            ->assertOk()
            ->assertSee('HPP / Avg Cost')
            ->assertSee('487.500');

        // Admin melihat HPP di show
        $this->actingAs($this->admin)->get("/parts/{$this->part->id}")
            ->assertOk()
            ->assertSee('HPP / Avg Cost (Admin)')
            ->assertSee('487.500');
    }

    public function test_cashier_cannot_see_cost_price_on_parts_index_and_show(): void
    {
        // Kasir dilarang melihat kolom HPP di index
        $responseIndex = $this->actingAs($this->cashier)->get('/parts');
        $responseIndex->assertOk();
        $responseIndex->assertDontSee('HPP / Avg Cost');
        $responseIndex->assertDontSee('487.500');

        // Kasir dilarang melihat HPP di show
        $responseShow = $this->actingAs($this->cashier)->get("/parts/{$this->part->id}");
        $responseShow->assertOk();
        $responseShow->assertDontSee('HPP / Avg Cost');
        $responseShow->assertDontSee('487.500');
    }

    public function test_mechanic_cannot_see_cost_price_on_parts_index_and_show(): void
    {
        // Mekanik dilarang melihat kolom HPP di index
        $responseIndex = $this->actingAs($this->mechanic)->get('/parts');
        $responseIndex->assertOk();
        $responseIndex->assertDontSee('HPP / Avg Cost');
        $responseIndex->assertDontSee('487.500');

        // Mekanik dilarang melihat HPP di show
        $responseShow = $this->actingAs($this->mechanic)->get("/parts/{$this->part->id}");
        $responseShow->assertOk();
        $responseShow->assertDontSee('HPP / Avg Cost');
        $responseShow->assertDontSee('487.500');
    }
}
