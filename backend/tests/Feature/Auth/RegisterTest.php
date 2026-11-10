<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::selectOne(
            'select current_database() as database'
        )->database;

        if ($database !== 'smartweather_testing') {
            throw new \RuntimeException(
                'Test dihentikan: database harus smartweather_testing.'
            );
        }
    }

    public function test_it_registers_a_user_with_a_hashed_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa.test@example.com',
            'password' => 'LatihanSmart123!',
            'password_confirmation' => 'LatihanSmart123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil.')
            ->assertJsonPath(
                'data.email',
                'mahasiswa.test@example.com'
            )
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa.test@example.com',
        ]);

        $user = User::where(
            'email',
            'mahasiswa.test@example.com'
        )->firstOrFail();

        $this->assertNotSame('LatihanSmart123!', $user->password);

        $this->assertTrue(
            Hash::check('LatihanSmart123!', $user->password)
        );
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'terdaftar@example.com',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mahasiswa Baru',
            'email' => 'terdaftar@example.com',
            'password' => 'LatihanSmart123!',
            'password_confirmation' => 'LatihanSmart123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_mismatched_password_confirmation(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa.test@example.com',
            'password' => 'LatihanSmart123!',
            'password_confirmation' => 'KonfirmasiBerbeda!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }
}