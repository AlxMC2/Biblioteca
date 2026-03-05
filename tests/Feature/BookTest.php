<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class BookTest extends TestCase
{
    use RefreshDatabase; // Esto limpia la base de datos después de cada test

    protected function setUp(): void
    {
        parent::setUp();
        // Creamos los roles necesarios para los tests
        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'estudiante']);
    }

    /** @test */
    public function un_bibliotecario_puede_crear_un_libro()
    {
        // 1. Creamos el usuario y le asignamos el rol
        $admin = User::factory()->create();
        $admin->assignRole('bibliotecario');

        // 2. Hacemos la petición como ese usuario
        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/v1/books', [
                             'title' => 'Cien años de soledad',
                             'author' => 'Gabriel García Márquez',
                             'ISBN' => '1234567890'
                         ]);

        // 3. Verificamos el resultado esperado de tu matriz
        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['title' => 'Cien años de soledad']);
    }

    /** @test */
    public function un_estudiante_no_puede_crear_un_libro()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $response = $this->actingAs($estudiante, 'sanctum')
                         ->postJson('/api/v1/books', [
                             'title' => 'Libro Prohibido',
                             'author' => 'Autor X',
                             'ISBN' => '0987654321'
                         ]);

        // Verificamos que se le deniegue el acceso (403 Forbidden)
        $response->assertStatus(403);
    }
}
