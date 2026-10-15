<?php
// src/BaseDatos/ProductoConsultas.php — TODO el SQL de la tabla productos vive aquí. Solo consultas PREPARADAS.
// Regla de oro: los DATOS van en marcadores (:nombre o ?); nunca se concatenan en el texto SQL.
declare(strict_types=1);

namespace Apicultores\BaseDatos;

use Apicultores\Excepciones\Conflicto;
use Apicultores\Excepciones\NoEncontrado;
use PDO;
use PDOException;

final class ProductoConsultas
{
    private const SELECCION = 'SELECT p.codigo, p.nombre, p.categoria, p.precio, p.stock, p.socio_id, s.nombre AS socio
                               FROM productos p JOIN socios s ON s.id = p.socio_id';

    public function __construct(private readonly PDO $bd)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function todos(): array
    {
        return $this->bd->query(self::SELECCION . ' ORDER BY p.categoria, p.nombre')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function socios(): array
    {
        return $this->bd->query('SELECT id, nombre FROM socios ORDER BY nombre')->fetchAll();
    }

    public function contar(): int
    {
        return (int) $this->bd->query('SELECT COUNT(*) FROM productos')->fetchColumn();
    }

    public function valorInventario(): float
    {
        return (float) $this->bd->query('SELECT COALESCE(SUM(precio * stock), 0) FROM productos')->fetchColumn();
    }

    public function porCodigo(string $codigo): ?array
    {
        $consulta = $this->bd->prepare(self::SELECCION . ' WHERE p.codigo = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        return $consulta->fetch() ?: null;
    }

    public function existeSocio(int $id): bool
    {
        $consulta = $this->bd->prepare('SELECT 1 FROM socios WHERE id = ?');
        $consulta->execute([$id]);
        return $consulta->fetchColumn() !== false;
    }

    // TODO 1: búsqueda con LIKE y marcadores (:patron y :patron2: con preparación nativa no se repite un mismo nombre).
    public function buscar(string $texto = ''): array
    {
        $sql = self::SELECCION . ' WHERE p.nombre LIKE :patron OR p.codigo LIKE :patron2 ORDER BY p.categoria, p.nombre';
        $patron = '%' . self::escaparComodines($texto) . '%';
        $consulta = $this->bd->prepare($sql);
        $consulta->execute(['patron' => $patron, 'patron2' => $patron]);
        return $consulta->fetchAll();
    }

    // TODO 2: % y _ son comodines de LIKE; se escapan para buscarlos como texto literal (no es el escape HTML).
    public static function escaparComodines(string $texto): string
    {
        return addcslashes($texto, '%_\\');
    }

    // TODO 3: la base es la ÚLTIMA línea de defensa contra códigos repetidos.
    public function insertar(array $d): int
    {
        try {
            $consulta = $this->bd->prepare(
                'INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id)
                 VALUES (:codigo, :nombre, :categoria, :precio, :stock, :socio_id)');
            $consulta->execute([
                'codigo' => $d['codigo'], 'nombre' => $d['nombre'], 'categoria' => $d['categoria'],
                'precio' => $d['precio'], 'stock' => $d['stock'], 'socio_id' => $d['socio_id'],
            ]);
        } catch (PDOException $e) {
            // 1062 = entrada duplicada (UNIQUE): aunque la validación revisó el código,
            // otra persona pudo registrarlo un instante antes.
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new Conflicto("Ya existe un producto con el código {$d['codigo']}.", 0, $e);
            }
            throw $e;
        }
        return (int) $this->bd->lastInsertId();
    }

    // TODO 4: WHERE limita el cambio a UN producto. Sin WHERE se actualizarían todos.
    public function actualizar(string $codigo, array $d): void
    {
        $consulta = $this->bd->prepare(
            'UPDATE productos SET nombre = :nombre, categoria = :categoria, precio = :precio, stock = :stock, socio_id = :socio_id
             WHERE codigo = :codigo');
        $consulta->execute([
            'nombre' => $d['nombre'], 'categoria' => $d['categoria'], 'precio' => $d['precio'],
            'stock' => $d['stock'], 'socio_id' => $d['socio_id'], 'codigo' => $codigo,
        ]);
    }

    // TODO 5: rowCount() dice cuántas filas afectó: 0 significa que no existía ese código.
    public function eliminar(string $codigo): void
    {
        $consulta = $this->bd->prepare('DELETE FROM productos WHERE codigo = ?');
        $consulta->execute([$codigo]);
        if ($consulta->rowCount() === 0) {
            throw new NoEncontrado("No existe el producto $codigo.");
        }
    }
}
