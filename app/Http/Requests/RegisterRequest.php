<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'cedula' => 'nullable|unique:users,cedula',
            'telefono' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'direccion' => 'nullable|string',
        ];
    }
}
