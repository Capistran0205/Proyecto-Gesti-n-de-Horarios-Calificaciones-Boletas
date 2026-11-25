<?php

namespace App\Http\Requests\Concerns;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;

/**
 * Reemplaza a la regla "unique": además de avisar que el dato ya existe, indica si pertenece
 * a un alumno dado de baja (se puede reactivar) o a una cuenta de otro rol.
 */
trait ValidaDuplicadosAlumno
{
    // $idActual: id del alumno que se está editando (null en el alta)
    protected function validarDuplicados(Validator $validador, ?int $idActual, bool $incluirMatricula): void
    {
        $validador->after(function (Validator $validador) use ($idActual, $incluirMatricula) {
            $datos = $validador->getData();
            $errores = $validador->errors();

            if ($incluirMatricula && ! $errores->has('usuario')) {
                $dueno = $this->buscarUsuario('usuario', $datos['usuario'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('usuario', "La matrícula {$datos['usuario']} ya está registrada" . $this->etiqueta($dueno) . '.');
                }
            }

            if (! $errores->has('correo_electronico')) {
                $dueno = $this->buscarUsuario('correo_electronico', $datos['correo_electronico'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('correo_electronico', "El correo {$datos['correo_electronico']} ya está registrado" . $this->etiqueta($dueno) . '.');
                }
            }

            if (! $errores->has('curp')) {
                $dueno = $this->buscarAlumno('curp', $datos['curp'] ?? '', $idActual);
                if ($dueno) {
                    $errores->add('curp', "La CURP {$datos['curp']} ya está registrada" . $this->etiqueta($dueno) . '.');
                }
            }

            // El NSS es opcional: solo se revisa si se capturó
            if (! $errores->has('nss') && ! empty($datos['nss'])) {
                $dueno = $this->buscarAlumno('nss', $datos['nss'], $idActual);
                if ($dueno) {
                    $errores->add('nss', "El NSS {$datos['nss']} ya está registrado" . $this->etiqueta($dueno) . '.');
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

    private function buscarAlumno(string $columna, string $valor, ?int $idActual): ?User
    {
        $alumno = Alumno::where($columna, $valor)
            ->when($idActual, fn ($consulta) => $consulta->where('id_alumno', '!=', $idActual))
            ->first();

        return $alumno?->usuario;
    }

    private function etiqueta(User $dueno): string
    {
        if ((int) $dueno->rol !== User::ROL_ALUMNO) {
            return ' (cuenta de otro rol)';
        }

        return (int) $dueno->estado === User::ESTADO_BAJA ? ' (alumno dado de baja)' : '';
    }
}
