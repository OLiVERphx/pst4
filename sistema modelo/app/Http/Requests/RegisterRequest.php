<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'contrasena' => ['required', 'string', 'confirmed', 'min:8'],
            'cedula' => ['nullable', 'string', Rule::unique('users', 'cedula')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string'],
        ];
    }
}
