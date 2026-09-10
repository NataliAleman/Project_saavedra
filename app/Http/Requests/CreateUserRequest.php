<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'matricula' => 'required|unique:users',
            'nombre' => 'required|string',
            'a_paterno' => 'required|string',
            'a_materno' => 'required|string',
            'contrasena' => 'required|string',
            'perfil' => 'required',
            'turno' => 'required|string',
            'planta' => 'required|string',
            'area' => 'required|string',
            'puesto' => 'required|string',
        ];
    }
}
