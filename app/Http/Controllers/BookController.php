<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);
        $books = Book::when($request->has('title'), function ($query) use ($request) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        })->when($request->has('isbn'), function ($query) use ($request) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        })->when($request->has('is_available'), function ($query) use ($request) {
            $query->where('is_available', $request->boolean('is_available'));
        })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

     public function show(Book $book)
    {
        $this->authorize('view', $book);
        return response()->json(BookResource::make($book));
    }

    public function store(StoreBookRequest $request)
    {
        $this->authorize('create', Book::class);
        
        $book = Book::create($request->validated());

        return response()->json(BookResource::make($book), 201);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);
        
        $book->update($request->validated());

        return response()->json(BookResource::make($book));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        
        // Buena práctica: Verificar relaciones antes de eliminar
        if ($book->loans()->whereNull('return_at')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un libro con préstamos activos'
            ], 422);
        }

        $book->delete();

        return response()->json([
            'message' => 'Libro eliminado exitosamente'
        ], 200);
    }
}
