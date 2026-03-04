<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Role::firstOrCreate(['name' => 'bibliotecario']);
        Role::firstOrCreate(['name' => 'docente']);
        Role::firstOrCreate(['name' => 'estudiante']);
$bibliotecario = User::firstOrCreate(
            ['email' => 'biblio@biblioteca.com'],
            ['name' => 'Juan Bibliotecario', 'password' => bcrypt('password')]
        );
        $bibliotecario->assignRole('bibliotecario');

        $docente = User::firstOrCreate(
            ['email' => 'docente@biblioteca.com'],
            ['name' => 'Maria Docente', 'password' => bcrypt('password')]
        );
        $docente->assignRole('docente');

        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@biblioteca.com'],
            ['name' => 'Pedro Estudiante', 'password' => bcrypt('password')]
        );
        $estudiante->assignRole('estudiante');

        $this->call([BookSeeder::class]);
    }
}
