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
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'estudiante']);
    }

    public function test_lista_libros_correctamente()
    {

        Book::factory()->count(3)->create();
        $user = User::factory()->create();


        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/books');


        $response->assertStatus(200);

    }

    public function test_ver_detalle_de_libro()
    {

        $book = Book::factory()->create();
        $user = User::factory()->create();


        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/books/{$book->id}");


        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $book->title]);
    }

    public function test_bibliotecario_puede_crear_libro()
    {

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');


        $response = $this->actingAs($bibliotecario, 'sanctum')
            ->postJson('/api/v1/books', [
            'title' => 'Cien años de soledad',
            'description' => 'Novela del realismo mágico',
            'ISBN' => '9780060883287',
            'total_copies' => 5,
            'available_copies' => 5,
            'is_available' => true,
        ]);


        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['title' => 'Cien años de soledad']);
    }

    public function test_docente_no_puede_crear_libro()
    {

        $docente = User::factory()->create();
        $docente->assignRole('docente');


        $response = $this->actingAs($docente, 'sanctum')
            ->postJson('/api/v1/books', [
            'title' => 'Libro Prohibido',
            'description' => 'Descripcion de prueba',
            'ISBN' => '9780060883288',
            'total_copies' => 3,
            'available_copies' => 3,
            'is_available' => true,
        ]);


        $response->assertStatus(403);
    }

    public function test_bibliotecario_puede_actualizar_libro()
    {

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();


        $response = $this->actingAs($bibliotecario, 'sanctum')
            ->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Título Actualizado',
            'description' => 'Descripción actualizada',
            'ISBN' => '9780060883211',
            'total_copies' => 10,
            'available_copies' => 10,
            'is_available' => true,
        ]);


        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Título Actualizado'
        ]);
    }
}