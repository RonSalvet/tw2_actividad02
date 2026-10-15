<?php
// src/Validacion/ValidadorSocio.php — Validación de los datos de un socio (en el servidor, siempre).
declare(strict_types=1);

namespace Apicultores\Validacion;

use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Repositorios\SocioRepositorio;

final class ValidadorSocio
{
    public function __construct(private readonly SocioRepositorio $socios)
    {
    }

    /**
     * @param int|null $id null al registrar; el id del socio al editar (para no chocar consigo mismo)
     * @return array{nombre: string, comunidad: string, telefono: ?string}
     * @throws DatosInvalidos
     */
    public function validar(array $entrada, ?int $id = null): array
    {
        foreach ($entrada as $valor) {
            if (!is_scalar($valor) && $valor !== null) {
                throw new DatosInvalidos(['general' => 'Los datos enviados no tienen un formato válido.']);
            }
            if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                throw new DatosInvalidos(['general' => 'Los datos enviados no tienen una codificación válida (UTF-8).']);
            }
        }
        $errores = [];
        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        $comunidad = trim((string) ($entrada['comunidad'] ?? ''));
        $telefono = trim((string) ($entrada['telefono'] ?? ''));

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 80) {
            $errores['nombre'] = 'El nombre debe tener entre 3 y 80 caracteres.';
        } elseif ($this->socios->existeNombre($nombre, $id)) {
            $errores['nombre'] = "Ya existe un socio llamado $nombre.";
        }
        if (mb_strlen($comunidad) < 2 || mb_strlen($comunidad) > 60) {
            $errores['comunidad'] = 'La comunidad debe tener entre 2 y 60 caracteres.';
        }
        if ($telefono !== '' && !preg_match('/^[0-9]{7,12}$/', $telefono)) {
            $errores['telefono'] = 'El teléfono debe tener entre 7 y 12 dígitos (déjalo vacío si no tiene).';
        }
        if ($errores !== []) {
            throw new DatosInvalidos($errores);
        }
        return ['nombre' => $nombre, 'comunidad' => $comunidad, 'telefono' => $telefono === '' ? null : $telefono];
    }
}
