<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'estudiante']);
    }

    public function test_prestar_libro_disponible_disminuye_el_stock()
    {
        // Preparacion
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create([
            'available_copies' => 3,
            'is_available'     => true,
        ]);

        // Ejecucion
        $response = $this->actingAs($estudiante, 'sanctum')
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
            ]);

        // Verificacion
        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 2,
        ]);
        $this->assertDatabaseHas('loans', [
            'book_id'        => $book->id,
            'requester_name' => $estudiante->name,
            'return_at'      => null,
        ]);
    }

    public function test_no_se_puede_prestar_un_libro_sin_stock()
    {
        // Preparacion
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create([
            'available_copies' => 0,
            'is_available'     => false,
        ]);

        // Ejecucion
        $response = $this->actingAs($estudiante, 'sanctum')
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
            ]);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Book is not available']);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_usuario_no_autenticado_no_puede_prestar()
    {
        // Preparacion
        $book = Book::factory()->create([
            'available_copies' => 3,
            'is_available'     => true,
        ]);

        // Ejecucion
        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
        ]);

        // Verificacion
        $response->assertStatus(401);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_devolver_libro_prestado_aumenta_el_stock()
    {
        // Preparacion
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create([
            'available_copies' => 1,
            'is_available'     => true,
        ]);
        $loan = Loan::factory()->create([
            'book_id'        => $book->id,
            'requester_name' => $estudiante->name,
            'return_at'      => null,
        ]);

        // Ejecucion
        $response = $this->actingAs($estudiante, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        // Verificacion
        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 2,
            'is_available'     => true,
        ]);
        $this->assertNotNull(Loan::find($loan->id)->return_at);
    }
}
