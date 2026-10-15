<?php
// verificar.php — Comprueba el Laboratorio 8 (MySQL, PDO y consultas preparadas). Uso: php verificar.php
// OJO: restaura los datos de prueba al empezar y al terminar. Úsalo SOLO con la base educativa de este laboratorio.
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL;
    exit(1);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\BaseDatos\ProductoConsultas;
use Apicultores\Excepciones\{Conflicto, NoEncontrado};

function lanza(string $clase, callable $accion): bool
{
    try {
        $accion();
    } catch (Throwable $e) {
        return $e instanceof $clase;
    }
    return false;
}

/** Deja la base como en sql/02_datos.sql (3 socios y 6 productos). */
function restaurar(PDO $bd): void
{
    $bd->exec('SET FOREIGN_KEY_CHECKS = 0');
    $bd->exec('TRUNCATE TABLE productos');
    $bd->exec('TRUNCATE TABLE socios');
    $bd->exec('SET FOREIGN_KEY_CHECKS = 1');
    $sql = (string) file_get_contents(__DIR__ . '/sql/02_datos.sql');
    $sql = (string) preg_replace('/^--.*$/m', '', $sql);            // quita comentarios
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
        $bd->exec($sentencia);
    }
}

try {
    $bd = Conexion::obtener();
    restaurar($bd);
} catch (Throwable $e) {
    echo "❌ No se pudo conectar a MySQL (" . $e->getMessage() . ")." . PHP_EOL;
    echo "   Revisa que el servicio mysql esté sano:  docker compose ps" . PHP_EOL;
    exit(1);
}

$q = new ProductoConsultas($bd);
$v = new Verificador();
$estructura = fn(string $sql, array $p = []) => (function () use ($bd, $sql, $p) { $c = $bd->prepare($sql); $c->execute($p); return $c->fetchAll(); })();
$sqlstate = function (callable $accion): ?string {
    try {
        $accion();
    } catch (PDOException $e) {
        return (string) $e->getCode();
    }
    return null;
};

// --- Conexión y estructura ---
$v->revisar('config/bd.php lee la configuración de variables de entorno (BD_HOST, BD_PUERTO…)', function () {
    $antesHost = getenv('BD_HOST');
    $antesPuerto = getenv('BD_PUERTO');
    putenv('BD_HOST=servidor-de-prueba');
    putenv('BD_PUERTO=3307');
    $c = require __DIR__ . '/config/bd.php';
    // Restaurar el entorno real para no afectar a las demás comprobaciones
    putenv($antesHost === false ? 'BD_HOST' : "BD_HOST=$antesHost");
    putenv($antesPuerto === false ? 'BD_PUERTO' : "BD_PUERTO=$antesPuerto");
    return $c['host'] === 'servidor-de-prueba' && $c['puerto'] === 3307;
});
$v->revisar('PDO: ERRMODE_EXCEPTION, FETCH_ASSOC y consultas preparadas reales (EMULATE_PREPARES = false)', fn() =>
    $bd->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION
    && $bd->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE) === PDO::FETCH_ASSOC
    && $bd->getAttribute(PDO::ATTR_EMULATE_PREPARES) == false);
$v->revisar('La base tiene las tablas socios y productos con 3 socios y 6 productos iniciales', fn() =>
    $q->contar() === 6 && (int) $bd->query('SELECT COUNT(*) FROM socios')->fetchColumn() === 3
    && count($estructura("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('socios','productos')")) === 2);
$v->revisar('precio es DECIMAL y codigo es UNIQUE', fn() =>
    ($estructura("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'precio'")[0]['DATA_TYPE'] ?? '') === 'decimal'
    && count($estructura("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'codigo' AND NON_UNIQUE = 0")) === 1);
// MySQL 8 informa CHECK con SQLSTATE HY000 (error 3819); MariaDB usa 23000: basta con que la base lo rechace.
$v->revisar('CHECK: la base rechaza precio ≤ 0 y stock negativo', fn() =>
    $sqlstate(fn() => $bd->exec("INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES ('BAD-01','x','Miel',0,1,1)")) !== null
    && $sqlstate(fn() => $bd->exec("INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES ('BAD-02','x','Miel',5,-1,1)")) !== null);
$v->revisar('FOREIGN KEY: la base rechaza un producto de un socio que no existe', fn() =>
    $sqlstate(fn() => $bd->exec("INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES ('BAD-03','x','Miel',5,1,999)")) === '23000'
    && $q->contar() === 6);

// --- Consultas preparadas ---
$v->revisar('todos() devuelve 6 productos con el nombre de su socio (JOIN)', function () use ($q) {
    $t = $q->todos();
    return count($t) === 6 && isset($t[0]['socio']) && in_array('Rosa Chuvé', array_column($t, 'socio'), true);
});
$v->revisar('buscar("miel") devuelve 3 productos y buscar("MIE-1K") devuelve 1', fn() =>
    count($q->buscar('miel')) === 3 && count($q->buscar('MIE-1K')) === 1 && count($q->buscar('')) === 6);
$v->revisar('Búsqueda segura: comillas, "%" y SQL inyectado se tratan como texto, no como instrucciones', fn() =>
    $q->buscar("'") === [] && $q->buscar('%') === [] && $q->buscar('_') === []
    && $q->buscar("x' OR '1'='1") === [] && $q->buscar("'; DROP TABLE productos; --") === [] && $q->contar() === 6
    && ProductoConsultas::escaparComodines('50%_\\') === '50\\%\\_\\\\');
$v->revisar('insertar() guarda el producto y devuelve su id', function () use ($q) {
    $id = $q->insertar(['codigo' => 'TST-01', 'nombre' => 'Producto de prueba', 'categoria' => 'Cera', 'precio' => '25.50', 'stock' => 7, 'socio_id' => 2]);
    $p = $q->porCodigo('TST-01');
    return $id > 0 && $p !== null && $p['nombre'] === 'Producto de prueba' && $p['precio'] === '25.50' && $p['socio'] === 'Mario Surubí' && $q->contar() === 7;
});
$v->revisar('insertar() con un código repetido → Conflicto (error 1062 de MySQL conservado como causa)', function () use ($q) {
    try {
        $q->insertar(['codigo' => 'TST-01', 'nombre' => 'Otro', 'categoria' => 'Miel', 'precio' => '10.00', 'stock' => 1, 'socio_id' => 1]);
    } catch (Conflicto $e) {
        return $e->getPrevious() instanceof PDOException && ($e->getPrevious()->errorInfo[1] ?? null) === 1062 && $q->contar() === 7;
    }
    return false;
});
$v->revisar('actualizar() cambia solo el producto indicado (WHERE)', function () use ($q) {
    $q->actualizar('TST-01', ['nombre' => 'Prueba editada', 'categoria' => 'Derivados', 'precio' => '30.00', 'stock' => 9, 'socio_id' => 3]);
    $p = $q->porCodigo('TST-01');
    $otro = $q->porCodigo('MIE-500');
    return $p['nombre'] === 'Prueba editada' && $p['stock'] === 9 && $p['precio'] === '30.00' && $p['socio'] === 'Elena Masabí'
        && $otro['nombre'] === 'Miel multifloral 500 g' && $otro['stock'] === 32 && $q->contar() === 7;
});
$v->revisar('eliminar() borra uno y un código inexistente → NoEncontrado', function () use ($q) {
    $q->eliminar('TST-01');
    return $q->porCodigo('TST-01') === null && $q->contar() === 6 && lanza(NoEncontrado::class, fn() => $q->eliminar('TST-01'));
});

// --- Páginas ---
$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php');
$v->revisar('/productos lista 6 productos, escapa "Polen &amp; propóleo", busca "miel" (3) y no ejecuta <script>', function () use ($v) {
    $lista = $v->pedir('/productos');
    $miel = $v->pedir('/productos?q=miel');
    $xss = $v->pedir('/productos?q=' . rawurlencode('<script>alert(1)</script>'));
    return $lista['estado'] === 200 && substr_count($lista['cuerpo'], '<td>MIE-') + substr_count($lista['cuerpo'], '<td>POL-') + substr_count($lista['cuerpo'], '<td>PRO-') + substr_count($lista['cuerpo'], '<td>CER-') === 6
        && str_contains($lista['cuerpo'], 'Polen &amp; propóleo') && str_contains($lista['cuerpo'], 'Bs 5.030,00')
        && substr_count($miel['cuerpo'], '<td>MIE-') === 3
        && str_contains($xss['cuerpo'], '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($xss['cuerpo'], '<script>alert');
});
$v->revisar('Formularios: alta válida → 303; datos inválidos o código repetido → 422; producto inexistente → 404', function () use ($v, $q) {
    $ok = $v->pedir('/productos', 'POST', ['codigo' => 'web-01', 'nombre' => 'Producto web', 'categoria' => 'Miel', 'precio' => '12,50', 'stock' => '3', 'socio_id' => '1']);
    $malo = $v->pedir('/productos', 'POST', ['codigo' => 'x', 'nombre' => '', 'categoria' => 'Hongos', 'precio' => '-5', 'stock' => '-1', 'socio_id' => '99']);
    $dup = $v->pedir('/productos', 'POST', ['codigo' => 'MIE-500', 'nombre' => 'Otra', 'categoria' => 'Miel', 'precio' => '10', 'stock' => '1', 'socio_id' => '1']);
    $edit = $v->pedir('/productos/WEB-01/editar', 'POST', ['nombre' => 'Producto web 2', 'categoria' => 'Cera', 'precio' => '13', 'stock' => '4', 'socio_id' => '2']);
    $del = $v->pedir('/productos/WEB-01/eliminar', 'POST', []);
    return $ok['estado'] === 303 && str_contains($ok['cabeceras'], 'Location: /productos')
        && $malo['estado'] === 422 && substr_count($malo['cuerpo'], 'class="error"') === 6
        && $dup['estado'] === 422 && str_contains($dup['cuerpo'], 'Ya existe un producto con el código MIE-500')
        && $edit['estado'] === 303 && $del['estado'] === 303 && $q->porCodigo('WEB-01') === null
        && $v->pedir('/productos/NO-EXISTE/editar')['estado'] === 404
        && $v->pedir('/productos/NO-EXISTE/eliminar', 'POST', [])['estado'] === 404;
});

restaurar($bd);
exit($v->fin());
