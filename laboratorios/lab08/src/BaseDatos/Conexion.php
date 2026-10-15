<?php
// src/BaseDatos/Conexion.php — Crea UNA conexión PDO a MySQL con las opciones seguras del curso.
declare(strict_types=1);

namespace Apicultores\BaseDatos;

use PDO;

final class Conexion
{
    private static ?PDO $pdo = null;

    /** @param array{host:string,puerto:int,nombre:string,usuario:string,clave:string}|null $config */
    public static function obtener(?array $config = null): PDO
    {
        if (self::$pdo !== null && $config === null) {
            return self::$pdo;
        }
        $config ??= require __DIR__ . '/../../config/bd.php';
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['puerto'], $config['nombre']);

        return self::$pdo = new PDO($dsn, $config['usuario'], $config['clave'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // los errores de SQL son excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // cada fila es un arreglo asociativo
            // TODO 6: consultas preparadas REALES del servidor: la estructura SQL y los datos viajan por separado.
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
