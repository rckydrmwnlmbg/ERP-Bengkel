<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_registration_screen(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_submit_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    }

    public function test_cashier_and_mechanic_cannot_access_registration(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->actingAs($cashier)->get('/register')->assertForbidden();
        $this->actingAs($cashier)->post('/register', [
            'name' => 'Cashier Created',
            'email' => 'cashier_created@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->actingAs($mechanic)->get('/register')->assertForbidden();
        $this->actingAs($mechanic)->post('/register', [
            'name' => 'Mechanic Created',
            'email' => 'mechanic_created@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'cashier_created@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'mechanic_created@example.com']);
    }

    public function test_admin_can_access_registration_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/register');

        $response->assertStatus(200);
    }

    public function test_admin_can_register_new_user_with_lowest_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/register', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        // Memastikan admin yang mendaftarkan tetap login sebagai admin
        $this->assertAuthenticatedAs($admin);

        // Memastikan user baru tersimpan dengan peran paling rendah (mechanic) tanpa akses data keuangan
        $newUser = User::where('email', 'staff@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('mechanic', $newUser->role);
        $this->assertFalse($newUser->isAdmin());
    }
}
