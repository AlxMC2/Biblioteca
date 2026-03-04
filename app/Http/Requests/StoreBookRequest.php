<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'ISBN' => ['required', 'string', 'unique:books,ISBN'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['required', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio',
            'description.required' => 'La descripción es obligatoria',
            'ISBN.required' => 'El ISBN es obligatorio',
            'ISBN.unique' => 'Este ISBN ya está registrado',
            'total_copies.required' => 'El total de copias es obligatorio',
            'total_copies.min' => 'Debe haber al menos 1 copia',
            'available_copies.required' => 'Las copias disponibles son obligatorias',
        ];
    }
}
