<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDuplicadosAdministrador;
use App\Models\Administrador;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdministradorRequest extends FormRequest
{
    use ValidaDuplicadosAdministrador;

    // Normaliza antes de validar: correo en minúsculas, CURP y RFC en mayúsculas
    protected function prepareForValidation(): void
    {
        $this->merge([
            'usuario' => trim((string) $this->input('usuario')),
            'nombre' => $this->limpiarNombre($this->input('nombre')),
            'apellido_pat' => $this->limpiarNombre($this->input('apellido_pat')),
            'apellido_mat' => $this->limpiarNombre($this->input('apellido_mat')) ?: null,
            'correo_electronico' => mb_strtolower(trim((string) $this->input('correo_electronico'))),
            'curp' => mb_strtoupper(trim((string) $this->input('curp'))),
            'rfc' => mb_strtoupper(trim((string) $this->input('rfc'))),
        ]);
    }

    // Quita espacios de los extremos y colapsa los repetidos
    private function limpiarNombre($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor));
    }

    public function rules(): array
    {
        // La unicidad se revisa en validarDuplicados() para poder avisar si el dato es de un administrador de baja
        return [
            'usuario' => ['required', 'digits_between:4,30'],
            'nombre' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_pat' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_mat' => ['nullable', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'correo_electronico' => ['required', 'email', 'max:120'],
            'curp' => ['required', 'regex:' . Administrador::REGEX_CURP],
            'rfc' => ['required', 'regex:' . Administrador::REGEX_RFC],
            'contrasena' => ['required', 'string', 'min:8'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarDuplicados($validador, null, true);
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Escribe el número de empleado.',
            'usuario.digits_between' => 'El número de empleado solo puede contener números (de 4 a 30 dígitos).',
            'nombre.required' => 'Escribe el nombre.',
            'nombre.max' => 'El nombre no puede pasar de 60 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido_pat.required' => 'Escribe el apellido paterno.',
            'apellido_pat.max' => 'El apellido paterno no puede pasar de 60 caracteres.',
            'apellido_pat.regex' => 'El apellido paterno solo puede contener letras y espacios.',
            'apellido_mat.max' => 'El apellido materno no puede pasar de 60 caracteres.',
            'apellido_mat.regex' => 'El apellido materno solo puede contener letras y espacios.',
            'correo_electronico.required' => 'Escribe el correo electrónico.',
            'correo_electronico.email' => 'Escribe un correo electrónico válido.',
            'correo_electronico.max' => 'El correo no puede pasar de 120 caracteres.',
            'curp.required' => 'Escribe la CURP.',
            'curp.regex' => 'La CURP no tiene un formato válido (18 caracteres).',
            'rfc.required' => 'Escribe el RFC.',
            'rfc.regex' => 'El RFC no tiene un formato válido (13 caracteres).',
            'contrasena.required' => 'La contraseña temporal es obligatoria.',
            'contrasena.min' => 'La contraseña temporal debe tener al menos 8 caracteres.',
        ];
    }
}
