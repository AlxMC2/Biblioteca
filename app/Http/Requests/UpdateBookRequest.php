<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'author' => ['sometimes', 'string', 'max:255'],
            'ISBN' => ['sometimes', 'string', 'unique:books,ISBN,' . $this->book->id],
            'published_year' => ['sometimes', 'integer', 'min:1000', 'max:' . date('Y')],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
