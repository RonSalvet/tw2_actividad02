<?php
// demo_inyeccion.php — Demostración de inyección SQL: la MISMA entrada contra una consulta VULNERABLE (didáctica,
// solo lectura) y contra el repositorio real (consultas preparadas). Uso: docker compose exec -T php php demo_inyeccion.php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\Repositorios\ProductoRepositorioMysql;
use Apicultores\Repositorios\SocioRepositorioMysql;

$bd = Conexion::crear(require __DIR__ . '/config/bd.php');
$productos = new ProductoRepositorioMysql($bd);
$socios = new SocioRepositorioMysql($bd);

/** ¡MAL! El texto del usuario se pega dentro del SQL. Solo existe para esta demostración (SELECT, nunca se usa en la app). */
function buscarVulnerable(PDO $bd, string $q): array
{
    $sql = "SELECT codigo, nombre, categoria FROM productos WHERE nombre LIKE '%$q%'";
    try {
        return ['sql' => $sql, 'filas' => $bd->query($sql)->fetchAll(), 'error' => null];
    } catch (PDOException $e) {
        return ['sql' => $sql, 'filas' => [], 'error' => $e->errorInfo[1] ?? $e->getCode()];
    }
}

$ataques = [
    'Condición siempre verdadera' => "zzz' OR 1=1 -- -",
    'UNION: robar socios y teléfonos' => "zzz' UNION SELECT nombre, comunidad, COALESCE(telefono, '-') FROM socios -- -",
    'Comilla suelta (rompe el SQL)' => "'",
];

$antes = (int) $bd->query('SELECT COUNT(*) FROM productos')->fetchColumn();
echo "DEMOSTRACIÓN DE INYECCIÓN SQL — productos en la tabla: $antes\n";
foreach ($ataques as $nombre => $entrada) {
    echo str_repeat('=', 78) . "\n$nombre\n  entrada del usuario : $entrada\n";
    $mal = buscarVulnerable($bd, $entrada);
    echo "  [VULNERABLE] SQL ejecutado: {$mal['sql']}\n";
    echo $mal['error'] !== null
        ? "  [VULNERABLE] resultado: ERROR de MySQL {$mal['error']} (la entrada rompió la consulta)\n"
        : "  [VULNERABLE] resultado: " . count($mal['filas']) . " filas" . (count($mal['filas']) > 0 ? ' → ejemplo: ' . implode(' | ', $mal['filas'][count($mal['filas']) - 1]) : '') . "\n";
    $bien = $productos->buscar($entrada, null, 'nombre', 1, 10);
    $bienSocios = $socios->buscar($entrada, 1, 10);
    echo "  [SEGURO    ] consulta preparada: WHERE (p.nombre LIKE :patron OR p.codigo LIKE :patron2) → {$bien->total} productos, {$bienSocios->total} socios\n";
}
$despues = (int) $bd->query('SELECT COUNT(*) FROM productos')->fetchColumn();
echo str_repeat('=', 78) . "\nTabla productos después de todos los ataques: $despues (antes: $antes) → " . ($antes === $despues ? 'INTACTA' : '¡ALTERADA!') . "\n";
