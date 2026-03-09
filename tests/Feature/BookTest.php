<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'estudiante']);
    }

    public function test_un_bibliotecario_puede_crear_un_libro()
    {
        // Preparacion
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        // Ejecucion
        $response = $this->actingAs($bibliotecario, 'sanctum')
            ->postJson('/api/v1/books', [
                'title'            => 'Cien años de soledad',
                'description'      => 'Novela del realismo mágico',
                'ISBN'             => '9780060883287',
                'total_copies'     => 5,
                'available_copies' => 5,
                'is_available'     => true,
            ]);

        // Verificacion
        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['title' => 'Cien años de soledad']);
    }

    public function test_un_estudiante_no_puede_crear_un_libro()
    {
        // Preparacion
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        // Ejecucion
        $response = $this->actingAs($estudiante, 'sanctum')
            ->postJson('/api/v1/books', [
                'title'            => 'Libro Prohibido',
                'description'      => 'Descripcion de prueba',
                'ISBN'             => '9780060883288',
                'total_copies'     => 3,
                'available_copies' => 3,
                'is_available'     => true,
            ]);

        // Verificacion
        $response->assertStatus(403);
    }

    public function test_bibliotecario_puede_eliminar_permanentemente_un_libro()
    {
        // Preparacion
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();

        // Ejecucion
        $response = $this->actingAs($bibliotecario, 'sanctum')
            ->deleteJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
