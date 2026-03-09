<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Cualquier usuario autenticado puede ver el historial.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Solo docentes y estudiantes pueden prestar libros.
     * El bibliotecario gestiona libros, no pide préstamos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['docente', 'estudiante']);
    }

    /**
     * Solo docentes y estudiantes pueden devolver préstamos.
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->hasRole(['docente', 'estudiante']);
    }
}
