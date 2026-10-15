<?php
// demo.php — Recorrido por consola: php demo.php
declare(strict_types=1);
require __DIR__ . '/src/cargar.php';

$miel = new Categoria('Miel', 'Miel pura de la Chiquitanía');
$p = new Producto('MIE-500', 'Miel multifloral 500 g', $miel, 45.0, 32, 'Rosa Chuvé');

echo "1) Un objeto:            ", $p->nombre(), ' — ', bs($p->precio()), PHP_EOL;
echo "2) Método con reglas:     precio de feria ", bs($p->precioConDescuento(10)), PHP_EOL;

$p->vender(30);
echo "3) Después de vender 30:  stock ", $p->stock(), ' (', $p->estadoStock(), ')', PHP_EOL;

try {
    $p->vender(5);
} catch (DomainException $e) {
    echo "4) Vender 5 más:          ", $e->getMessage(), PHP_EOL;
}

try {
    $p->stock = 1000;                       // encapsulamiento: nadie puede "saltarse" las reglas
} catch (Error $e) {
    echo "5) \$p->stock = 1000:      ", $e->getMessage(), PHP_EOL;
}

try {
    new Producto('miel', 'Miel', $miel, -5, 3);
} catch (InvalidArgumentException $e) {
    echo "6) Objeto inválido:       ", $e->getMessage(), PHP_EOL;
}

$otra = $p;                                  // los objetos se pasan por REFERENCIA (identificador)
$otra->reponer(10);
echo "7) \$otra = \$p; reponer:   \$p->stock() = ", $p->stock(), '  ($otra === $p: ', var_export($otra === $p, true), ')', PHP_EOL;
$copia = clone $p;
$copia->reponer(100);
echo "8) clone:                 \$p->stock() = ", $p->stock(), ', $copia->stock() = ', $copia->stock(), PHP_EOL;

$catalogo = Catalogo::desdeArchivo(ARCHIVO_PRODUCTOS);
echo "9) Catálogo:              ", $catalogo->cantidad(), ' productos, inventario ', bs($catalogo->valorInventario()), PHP_EOL;
echo "10) Más barato y más caro: ", $catalogo->ordenadosPorPrecio()[0]->nombre(), ' / ', $catalogo->ordenadosPorPrecio(false)[0]->nombre(), PHP_EOL;
