<?php

namespace App\Http\Requests\Concerns;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;

/**
 * Reemplaza a la regla "unique": además de avisar que el dato ya existe, indica si pertenece
 * a un docente dado de baja (se puede reactivar) o a una cuenta de otro rol.
 */
trait ValidaDuplicadosDocente
{
    // $idActual: id del docente que se está editando (null en el alta)
    protected function validarDuplicados(Validator $validador, ?int $idActual, bool $incluirUsuario): void
    {
        $validador->after(function (Validator $validador) use ($idActual, $incluirUsuario) {
            $datos = $validador->getData();
            $errores = $validador->errors();

            if ($incluirUsuario && ! $errores->has('usuario')) {
                $dueno = $this->buscarUsuario('usuario', $datos['usuario'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('usuario', "El número de empleado {$datos['usuario']} ya está registrado" . $this->etiqueta($dueno) . '.');
                }
            }

            if (! $errores->has('correo_electronico')) {
                $dueno = $this->buscarUsuario('correo_electronico', $datos['correo_electronico'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('correo_electronico', "El correo {$datos['correo_electronico']} ya está registrado" . $this->etiqueta($dueno) . '.');
                }
            }

            if (! $errores->has('curp')) {
                $dueno = $this->buscarDocente('curp', $datos['curp'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('curp', "La CURP {$datos['curp']} ya está registrada" . $this->etiqueta($dueno) . '.');
                }
            }

            if (! $errores->has('rfc')) {
                $dueno = $this->buscarDocente('rfc', $datos['rfc'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('rfc', "El RFC {$datos['rfc']} ya está registrado" . $this->etiqueta($dueno) . '.');
                }
            }
        });
    }

    private function buscarUsuario(string $columna, string $valor, ?int $idActual): ?User
    {
        return User::where($columna, $valor)
            ->when($idActual, fn ($consulta) => $consulta->where('id', '!=', $idActual))
            ->first();
    }

    private function buscarDocente(string $columna, string $valor, ?int $idActual): ?User
    {
        $docente = Docente::where($columna, $valor)
            ->when($idActual, fn ($consulta) => $consulta->where('id_docente', '!=', $idActual))
            ->first();

        return $docente?->usuario;
    }

    private function etiqueta(User $dueno): string
    {
        if ((int) $dueno->rol !== User::ROL_DOCENTE) {
            return ' (cuenta de otro rol)';
        }

        return (int) $dueno->estado === User::ESTADO_BAJA ? ' (docente dado de baja)' : '';
    }
}
