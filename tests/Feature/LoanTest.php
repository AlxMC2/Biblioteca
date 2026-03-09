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

    public function test_no_se_puede_marcar_como_devuelto_un_prestamo_ya_devuelto()
    {
        // Preparacion
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create([
            'available_copies' => 2,
            'is_available'     => true,
        ]);
        $loan = Loan::factory()->returned()->create([
            'book_id'        => $book->id,
            'requester_name' => $estudiante->name,
        ]);

        // Ejecucion
        $response = $this->actingAs($estudiante, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        // Verificacion
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Loan already returned']);
        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 2,
        ]);
    }

    public function test_un_usuario_no_puede_devolver_el_prestamo_de_otro_usuario()
    {
        // Preparacion
        $duenoPrestamo = User::factory()->create();
        $duenoPrestamo->assignRole('estudiante');

        $usuarioDistinto = User::factory()->create();
        $usuarioDistinto->assignRole('estudiante');

        $book = Book::factory()->create([
            'available_copies' => 1,
            'is_available'     => true,
        ]);

        $loan = Loan::factory()->create([
            'book_id'        => $book->id,
            'requester_name' => $duenoPrestamo->name,
            'return_at'      => null,
        ]);

        // Ejecucion
        $response = $this->actingAs($usuarioDistinto, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        // Verificacion
        $response->assertStatus(403);
        $this->assertNull($loan->fresh()->return_at);
        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 1,
        ]);
    }

    public function test_el_usuario_ve_propios_prestamos()
    {
        // Preparacion
        $usuario = User::factory()->create();
        $usuario->assignRole('estudiante');

        $otroUsuario = User::factory()->create();
        $otroUsuario->assignRole('estudiante');

        $book = Book::factory()->create();

        $prestamoAntiguo = Loan::factory()->create([
            'book_id'        => $book->id,
            'requester_name' => $usuario->name,
            'created_at'     => now()->subDays(3),
        ]);

        $prestamoReciente = Loan::factory()->create([
            'book_id'        => $book->id,
            'requester_name' => $usuario->name,
            'created_at'     => now()->subDay(),
        ]);

        $prestamoAjeno = Loan::factory()->create([
            'book_id'        => $book->id,
            'requester_name' => $otroUsuario->name,
            'created_at'     => now(),
        ]);

        // Ejecucion
        $response = $this->actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/loans');

        // Verificacion
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $prestamoReciente->id);
        $response->assertJsonPath('data.1.id', $prestamoAntiguo->id);
        $response->assertJsonMissing(['id' => $prestamoAjeno->id]);
    }
}
