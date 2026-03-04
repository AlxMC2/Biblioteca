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
            'description' => ['sometimes', 'string', 'max:1000'],
            'ISBN' => ['sometimes', 'string', 'unique:books,ISBN,' . $this->book->id],
            'total_copies' => ['sometimes', 'integer', 'min:1'],
            'available_copies' => ['sometimes', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ISBN.unique' => 'Este ISBN ya está en uso por otro libro',
            'total_copies.min' => 'Debe haber al menos 1 copia',
        ];
    }
}
