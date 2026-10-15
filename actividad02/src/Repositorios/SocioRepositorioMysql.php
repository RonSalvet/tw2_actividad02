<?php
// src/Repositorios/SocioRepositorioMysql.php — CRUD de socios con MySQL + PDO. SIEMPRE consultas preparadas.
// Regla de oro: los DATOS van en marcadores (:nombre o ?); nunca se concatenan en el texto SQL.
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Excepciones\Conflicto;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Socio;
use PDO;
use PDOException;

final class SocioRepositorioMysql implements SocioRepositorio
{
    public function __construct(private readonly PDO $bd)
    {
    }

    public function todos(): array
    {
        $filas = $this->bd->query('SELECT id, nombre, comunidad, telefono FROM socios ORDER BY nombre')->fetchAll();
        return array_map($this->hidratar(...), $filas);
    }

    public function porId(int $id): ?Socio
    {
        $c = $this->bd->prepare('SELECT id, nombre, comunidad, telefono FROM socios WHERE id = ?');
        $c->execute([$id]);
        $f = $c->fetch();
        return $f ? $this->hidratar($f) : null;
    }

    public function buscar(string $texto, int $pagina, int $porPagina): Pagina
    {
        // Los comodines de LIKE (% _ \) que escriba el usuario se buscan como texto literal
        $patron = '%' . addcslashes($texto, '%_\\') . '%';
        $valores = ['patron' => $patron, 'patron2' => $patron];
        $donde = ' WHERE (nombre LIKE :patron OR comunidad LIKE :patron2)';

        $c = $this->bd->prepare('SELECT COUNT(*) FROM socios' . $donde);
        $c->execute($valores);
        $total = (int) $c->fetchColumn();

        $c = $this->bd->prepare('SELECT id, nombre, comunidad, telefono FROM socios' . $donde . ' ORDER BY nombre LIMIT :limite OFFSET :desde');
        foreach ($valores as $nombre => $valor) {
            $c->bindValue(':' . $nombre, $valor);
        }
        $c->bindValue(':limite', $porPagina, PDO::PARAM_INT);                // LIMIT/OFFSET exigen ENTEROS
        $c->bindValue(':desde', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
        $c->execute();

        return new Pagina(array_map($this->hidratar(...), $c->fetchAll()), $total, $pagina, $porPagina);
    }

    public function existeNombre(string $nombre, ?int $exceptoId = null): bool
    {
        $c = $this->bd->prepare('SELECT 1 FROM socios WHERE nombre = :nombre AND id <> :id');
        $c->execute(['nombre' => $nombre, 'id' => $exceptoId ?? 0]);
        return $c->fetchColumn() !== false;
    }

    public function contarProductos(int $id): int
    {
        $c = $this->bd->prepare('SELECT COUNT(*) FROM productos WHERE socio_id = ?');
        $c->execute([$id]);
        return (int) $c->fetchColumn();
    }

    public function guardar(array $datos): int
    {
        $parametros = ['nombre' => $datos['nombre'], 'comunidad' => $datos['comunidad'], 'telefono' => $datos['telefono'] ?? null];
        try {
            if (isset($datos['id'])) {
                $c = $this->bd->prepare('UPDATE socios SET nombre = :nombre, comunidad = :comunidad, telefono = :telefono WHERE id = :id');
                $c->execute($parametros + ['id' => $datos['id']]);
                return (int) $datos['id'];
            }
            $c = $this->bd->prepare('INSERT INTO socios (nombre, comunidad, telefono) VALUES (:nombre, :comunidad, :telefono)');
            $c->execute($parametros);
            return (int) $this->bd->lastInsertId();
        } catch (PDOException $e) {
            // 1062 = entrada duplicada (UNIQUE nombre): otra persona pudo registrarlo un instante antes
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new Conflicto("Ya existe un socio llamado {$datos['nombre']}.", 0, $e);
            }
            throw $e;
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $c = $this->bd->prepare('DELETE FROM socios WHERE id = ?');
            $c->execute([$id]);
        } catch (PDOException $e) {
            // 1451 = la clave foránea lo impide: el socio todavía tiene productos
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw new Conflicto('No se puede eliminar: el socio todavía tiene productos registrados.', 0, $e);
            }
            throw $e;
        }
        if ($c->rowCount() === 0) {
            throw new NoEncontrado("No existe el socio $id.");
        }
    }

    /** Fila de MySQL → objeto Socio. */
    private function hidratar(array $f): Socio
    {
        return new Socio((int) $f['id'], $f['nombre'], $f['comunidad'], $f['telefono']);
    }
}
