<?php
// verificar_actividad2.php — Actividad 2: CRUD de socios y de productos con PDO + prueba de resistencia a la inyección SQL.
// Uso: docker compose exec -T php php verificar_actividad2.php        (restaura los datos al empezar y al terminar)
declare(strict_types=1);
require __DIR__ . '/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    exit("❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\Excepciones\Conflicto;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Repositorios\{ProductoRepositorioMysql, SocioRepositorioMysql};

try {
    $bd = Conexion::crear(require __DIR__ . '/config/bd.php');
} catch (PDOException $e) {
    exit("❌ No hay conexión con MySQL ({$e->getMessage()}).\n   Inicia la base de datos:  docker compose up -d" . PHP_EOL);
}
function restaurar(PDO $bd): void
{
    foreach (['01-esquema.sql', '02-datos.sql', '03-mas-datos.sql'] as $archivo) {
        $bd->exec((string) file_get_contents(__DIR__ . "/sql/$archivo"));
    }
}
function contar(PDO $bd, string $tabla): int
{
    return (int) $bd->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();    // $tabla es una constante de este archivo
}
/** Códigos de error de MySQL (errorInfo[1]) de una acción que debe fallar */
function sqlError(callable $accion): ?int
{
    try {
        $accion();
    } catch (PDOException $e) {
        return $e->errorInfo[1] ?? null;
    }
    return null;
}

restaurar($bd);
$productos = new ProductoRepositorioMysql($bd);
$socios = new SocioRepositorioMysql($bd);
$v = new Verificador();

// ---------- CRUD de socios con el repositorio (consultas preparadas) ----------
$v->revisar('SocioRepositorio: 8 socios; porId(3) = Elena Masabí; porId(999) = null', fn() =>
    count($socios->todos()) === 8 && $socios->porId(3)?->nombre === 'Elena Masabí' && $socios->porId(999) === null);
$v->revisar('SocioRepositorio::buscar() pagina: "velasco" → 3 resultados; sin filtro y de 5 en 5: 2 páginas, la 2.ª con 3 socios', function () use ($socios) {
    $p = $socios->buscar('velasco', 1, 5);
    $todos = $socios->buscar('', 2, 5);
    return $p->total === 3 && count($p->elementos) === 3 && $p->totalPaginas() === 1
        && $todos->total === 8 && $todos->totalPaginas() === 2 && count($todos->elementos) === 3;
});
$v->revisar('Registrar socio → id nuevo; existeNombre(); editar solo cambia ese socio', function () use ($socios) {
    $id = $socios->guardar(['nombre' => 'Pedro Prueba', 'comunidad' => 'San Javier', 'telefono' => null]);
    $creado = $socios->porId($id);
    $socios->guardar(['id' => $id, 'nombre' => 'Pedro Prueba', 'comunidad' => 'Concepción', 'telefono' => '70000000']);
    return $id > 8 && $creado?->comunidad === 'San Javier' && $socios->existeNombre('Pedro Prueba')
        && !$socios->existeNombre('Pedro Prueba', $id) && $socios->porId($id)?->comunidad === 'Concepción'
        && $socios->porId(1)?->comunidad === 'San Javier';
});
$v->revisar('Nombre repetido → Conflicto (error 1062 conservado como causa)', function () use ($socios) {
    try {
        $socios->guardar(['nombre' => 'Rosa Chuvé', 'comunidad' => 'X', 'telefono' => null]);
    } catch (Conflicto $e) {
        return $e->getPrevious() instanceof PDOException;
    }
    return false;
});
$v->revisar('Eliminar: un socio CON productos → Conflicto (FK 1451); uno sin productos se borra; inexistente → NoEncontrado', function () use ($socios, $bd) {
    $conflicto = false;
    try {
        $socios->eliminar(1);
    } catch (Conflicto) {
        $conflicto = true;
    }
    $id = (int) $bd->query("SELECT id FROM socios WHERE nombre = 'Pedro Prueba'")->fetchColumn();
    $socios->eliminar($id);
    $noEncontrado = false;
    try {
        $socios->eliminar($id);
    } catch (NoEncontrado) {
        $noEncontrado = true;
    }
    return $conflicto && $noEncontrado && $socios->porId($id) === null && $socios->contarProductos(1) === 2 && count($socios->todos()) === 8;
});

// ---------- CRUD por HTTP: formularios de socios ----------
$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php');
$lista = $v->pedir('/socios');
$v->revisar('/socios: lista paginada con búsqueda ("Mostrando 1–8 de 8"; ?q=san → 4)', fn() =>
    $lista['estado'] === 200 && str_contains($lista['cuerpo'], 'Mostrando 1–8 de 8')
    && str_contains($v->pedir('/socios?q=velasco')['cuerpo'], 'Mostrando 1–3 de 3'));
$alta = $v->pedir('/socios', 'POST', ['nombre' => 'Lucía Prueba', 'comunidad' => 'Roboré', 'telefono' => '71111111']);
$malo = $v->pedir('/socios', 'POST', ['nombre' => 'x', 'comunidad' => '', 'telefono' => 'abc']);
$dup = $v->pedir('/socios', 'POST', ['nombre' => 'Rosa Chuvé', 'comunidad' => 'X', 'telefono' => '']);
$idNuevo = (int) $bd->query("SELECT id FROM socios WHERE nombre = 'Lucía Prueba'")->fetchColumn();
$v->revisar('Formularios de socios: alta válida → 303; datos inválidos → 422 (3 errores); nombre repetido → 422', fn() =>
    $alta['estado'] === 303 && str_contains($alta['cabeceras'], 'Location: /socios?creado=') && $idNuevo > 0
    && $malo['estado'] === 422 && substr_count($malo['cuerpo'], 'class="error"') === 3
    && $dup['estado'] === 422 && str_contains($dup['cuerpo'], 'Ya existe un socio llamado Rosa Chuvé'));
$ed = $v->pedir("/socios/$idNuevo", 'POST', ['nombre' => 'Lucía Prueba', 'comunidad' => 'Santa Ana', 'telefono' => '']);
$v->revisar('Editar socio → 303 y el cambio queda en MySQL (teléfono vacío → NULL); GET del formulario muestra los datos', fn() =>
    $ed['estado'] === 303 && $socios->porId($idNuevo)?->comunidad === 'Santa Ana' && $socios->porId($idNuevo)?->telefono === null
    && str_contains($v->pedir("/socios/$idNuevo/editar")['cuerpo'], 'value="Santa Ana"'));
$v->revisar('Eliminar socio: con productos → 409 (no se borra); sin productos → 303; repetir → 404; GET → 405', fn() =>
    $v->pedir('/socios/1/eliminar', 'POST', [])['estado'] === 409 && $socios->porId(1) !== null
    && $v->pedir("/socios/$idNuevo/eliminar", 'POST', [])['estado'] === 303 && $socios->porId($idNuevo) === null
    && $v->pedir("/socios/$idNuevo/eliminar", 'POST', [])['estado'] === 404
    && $v->pedir('/socios/1/eliminar')['estado'] === 405
    && $v->pedir('/socios/abc')['estado'] === 404 && $v->pedir('/socios/nuevo')['estado'] === 200);

// ---------- CRUD de productos por HTTP (resumen) ----------
$nuevo = ['codigo' => 'ACT-02', 'nombre' => 'Producto Actividad 2', 'categoria' => 'Miel', 'precio' => '19,90', 'stock' => '7', 'socio_id' => '2'];
$v->revisar('CRUD de productos: registrar 303 → editar 303 → eliminar 303 (precio DECIMAL exacto 19.90)', function () use ($v, $productos, $nuevo) {
    $a = $v->pedir('/productos', 'POST', $nuevo);
    $p = $productos->porCodigo('ACT-02');
    $e = $v->pedir('/productos/ACT-02', 'POST', ['precio' => '21', 'stock' => '9'] + $nuevo);
    $editado = $productos->porCodigo('ACT-02');
    $d = $v->pedir('/productos/ACT-02/eliminar', 'POST', []);
    return $a['estado'] === 303 && $p?->precio === '19.90' && $e['estado'] === 303 && $editado?->precio === '21.00'
        && $editado->stock === 9 && $d['estado'] === 303 && $productos->porCodigo('ACT-02') === null;
});

// ---------- INYECCIÓN SQL: cada entrada del usuario con cargas de ataque ----------
$cargas = [
    "' OR '1'='1",
    "zzz' OR 1=1 -- -",
    "'; DROP TABLE productos; -- ",
    "' UNION SELECT id, nombre, comunidad, telefono, 1, 1, 1, 1, 1, 1 FROM socios -- -",
    "\" OR \"\"=\"",
    "%' AND SLEEP(3) AND '%'='",
    "\\",
    "%",
    "_",
];
$antes = ['productos' => contar($bd, 'productos'), 'socios' => contar($bd, 'socios')];
$sinFugas = fn(array $r): bool => $r['estado'] !== 500 && !str_contains($r['cuerpo'], 'SQLSTATE') && !str_contains($r['cuerpo'], 'syntax');

$v->revisar('Inyección en la búsqueda de productos (?q=): ninguna carga devuelve filas ni errores; las tablas siguen intactas', function () use ($v, $cargas, $sinFugas, $bd, $antes) {
    foreach ($cargas as $carga) {
        $r = $v->pedir('/productos?q=' . rawurlencode($carga));
        if (!$sinFugas($r) || !str_contains($r['cuerpo'], 'Sin resultados')) {
            return false;
        }
    }
    return contar($bd, 'productos') === $antes['productos'] && contar($bd, 'socios') === $antes['socios'];
});
$v->revisar('Inyección en la búsqueda de socios (?q=): sin filas, sin errores SQL; las tablas siguen intactas', function () use ($v, $cargas, $sinFugas, $bd, $antes) {
    foreach ($cargas as $carga) {
        $r = $v->pedir('/socios?q=' . rawurlencode($carga));
        if (!$sinFugas($r) || !str_contains($r['cuerpo'], 'Sin resultados')) {
            return false;
        }
    }
    return contar($bd, 'productos') === $antes['productos'] && contar($bd, 'socios') === $antes['socios'];
});
$v->revisar('Inyección en categoria, orden, pagina y en la ruta: se normalizan o dan 404; nunca llegan al SQL', function () use ($v, $sinFugas, $bd, $antes) {
    $peticiones = [
        '/productos?categoria=' . rawurlencode("Miel' OR '1'='1"),
        '/productos?orden=' . rawurlencode('p.precio; DROP TABLE productos'),
        '/productos?orden=' . rawurlencode('stock,(SELECT SLEEP(3))'),
        '/productos?pagina=' . rawurlencode('1; DROP TABLE socios'),
        '/productos?pagina=' . rawurlencode('1 UNION SELECT 1'),
        '/productos?q[]=' . rawurlencode("' OR 1=1"),
        '/productos/' . rawurlencode("' OR 1=1 -- -") . '/editar',
        '/socios/' . rawurlencode('1 OR 1=1'),
        '/socios/' . rawurlencode('1; DROP TABLE socios') . '/editar',
    ];
    foreach ($peticiones as $ruta) {
        $r = $v->pedir($ruta);
        if (!$sinFugas($r) || !in_array($r['estado'], [200, 404], true)) {
            return false;
        }
    }
    return contar($bd, 'productos') === $antes['productos'] && contar($bd, 'socios') === $antes['socios'];
});
$v->revisar('Inyección en los formularios: el ataque se guarda como TEXTO literal y se muestra escapado; nada se ejecuta', function () use ($v, $socios, $bd, $antes) {
    $texto = "Robert'); DROP TABLE socios;-- ";
    $r = $v->pedir('/socios', 'POST', ['nombre' => $texto, 'comunidad' => "x' OR '1'='1", 'telefono' => '']);
    $fila = $bd->query("SELECT id, nombre, comunidad FROM socios WHERE id = (SELECT MAX(id) FROM socios)")->fetch();
    $pagina = $v->pedir('/socios?q=Robert');
    $ok = $r['estado'] === 303 && $fila['nombre'] === trim($texto) && $fila['comunidad'] === "x' OR '1'='1"
        && str_contains($pagina['cuerpo'], 'Robert&#039;); DROP TABLE socios;--')
        && contar($bd, 'socios') === $antes['socios'] + 1;
    $v->pedir("/socios/{$fila['id']}/eliminar", 'POST', []);
    return $ok && contar($bd, 'socios') === $antes['socios'];
});
$v->revisar('Código fuente: ninguna consulta de src/ pega variables dentro del SQL (solo marcadores ? y :nombre)', function () {
    foreach (glob(__DIR__ . '/src/Repositorios/*Mysql.php') as $archivo) {
        $codigo = (string) file_get_contents($archivo);
        // ningún texto con SQL interpola variables ("... LIKE '%$texto%'"), salvo $ordenSql (sale de la lista permitida)
        preg_match_all('/"[^"\n]*"/', $codigo, $textos);
        foreach ($textos[0] as $texto) {
            if (preg_match('/\b(SELECT|WHERE|LIKE|INSERT|UPDATE|DELETE|ORDER)\b/i', $texto)
                && preg_match('/\$(?!ordenSql\b)\w+/', $texto)) {
                return false;
            }
        }
        // query() y exec() solo con SQL fijo (sin variables)
        if (preg_match('/->(query|exec)\(\s*["\'][^"\']*\$/', $codigo)) {
            return false;
        }
        // en prepare() solo se concatenan fragmentos que escribimos nosotros: $donde (condiciones con marcadores) y $ordenSql (lista permitida)
        preg_match_all('/->prepare\((.*?)\)\s*(?:->|;)/s', $codigo, $m);
        foreach ($m[1] as $argumento) {
            $sinPermitidos = preg_replace('/\$donde|\$sql|\$ordenSql|self::SELECCION/', '', $argumento);
            if (preg_match('/\$\w+/', (string) $sinPermitidos)) {
                return false;
            }
        }
    }
    return true;
});

@unlink(__DIR__ . '/datos/errores.log');
restaurar($bd);
exit($v->fin());
