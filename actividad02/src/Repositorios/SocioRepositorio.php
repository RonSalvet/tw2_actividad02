<?php
// src/Repositorios/SocioRepositorio.php — El CONTRATO del almacenamiento de socios (CRUD + búsqueda paginada).
// Igual que ProductoRepositorio: los controladores dependen de esta interfaz, no de MySQL.
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Modelo\Socio;

interface SocioRepositorio
{
    /** @return Socio[] todos, ordenados por nombre (para los <select>) */
    public function todos(): array;

    public function porId(int $id): ?Socio;

    /** Búsqueda por nombre o comunidad, paginada. */
    public function buscar(string $texto, int $pagina, int $porPagina): Pagina;

    public function existeNombre(string $nombre, ?int $exceptoId = null): bool;

    /** Cuántos productos tiene el socio (un socio con productos no se puede borrar). */
    public function contarProductos(int $id): int;

    /**
     * Inserta (si no trae 'id') o actualiza (si trae 'id'). Devuelve el id del socio.
     * @param array{id?: int, nombre: string, comunidad: string, telefono: ?string} $datos
     */
    public function guardar(array $datos): int;

    public function eliminar(int $id): void;
}
