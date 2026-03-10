<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'ISBN',
        'total_copies',
        'available_copies',
        'is_available',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
