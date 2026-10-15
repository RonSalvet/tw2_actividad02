<?php
// verificar.php — Comprueba el Laboratorio 5 (POO: Categoria, Producto, Catalogo). Uso: php verificar.php
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';
require __DIR__ . '/src/cargar.php';

/** true si $accion lanza una excepción de la clase $clase */
function lanza(string $clase, callable $accion): bool
{
    try {
        $accion();
    } catch (Throwable $e) {
        return $e instanceof $clase;
    }
    return false;
}

$v = new Verificador();
$miel = new Categoria('Miel');

// --- Producto ---
$v->revisar('Producto válido: getters y valor en stock (45 × 32 = 1440)', function () use ($miel) {
    $p = new Producto('MIE-500', 'Miel multifloral 500 g', $miel, 45.0, 32, 'Rosa Chuvé');
    return $p->codigo() === 'MIE-500' && $p->categoria() === $miel && $p->valorEnStock() === 1440.0;
});
$v->revisar('El constructor rechaza código, precio o stock inválidos (InvalidArgumentException)', fn() =>
    lanza(InvalidArgumentException::class, fn() => new Producto('miel', 'Miel', $miel, 45.0, 3))
    && lanza(InvalidArgumentException::class, fn() => new Producto('MIE-500', 'Miel', $miel, 0.0, 3))
    && lanza(InvalidArgumentException::class, fn() => new Producto('MIE-500', 'Miel', $miel, 45.0, -1)));
$v->revisar('vender() descuenta y no permite vender más de lo que hay (DomainException)', function () use ($miel) {
    $p = new Producto('MIE-500', 'Miel', $miel, 45.0, 10);
    $p->vender(7);
    return $p->stock() === 3 && lanza(DomainException::class, fn() => $p->vender(4)) && $p->stock() === 3
        && lanza(InvalidArgumentException::class, fn() => $p->vender(0));
});
$v->revisar('Encapsulamiento: $p->stock = 1000 no está permitido', fn() =>
    lanza(Error::class, function () use ($miel) { $p = new Producto('MIE-500', 'Miel', $miel, 45.0, 1); $p->stock = 1000; }));
$v->revisar('estadoStock: 0 → agotado, 5 → bajo, 6 → normal', fn() =>
    (new Producto('AAA-01', 'Uno', $miel, 1.0, 0))->estadoStock() === 'agotado'
    && (new Producto('AAA-02', 'Dos', $miel, 1.0, 5))->estadoStock() === 'bajo'
    && (new Producto('AAA-03', 'Tres', $miel, 1.0, 6))->estadoStock() === 'normal');
$v->revisar('precioConDescuento(10) de 85 → 76.5 y rechaza 60 %', function () use ($miel) {
    $p = new Producto('MIE-1K', 'Miel 1 kg', $miel, 85.0, 1);
    return $p->precioConDescuento(10) === 76.5 && lanza(InvalidArgumentException::class, fn() => $p->precioConDescuento(60));
});

// --- Catalogo ---
$c = Catalogo::desdeArchivo(__DIR__ . '/datos/productos.json');
$v->revisar('Catalogo::desdeArchivo carga 6 productos en 3 categorías', fn() => $c->cantidad() === 6 && count($c->categorias()) === 3);
$v->revisar('valorInventario() = 5030 (el mismo resultado que el lab 2)', fn() => $c->valorInventario() === 5030.0);
$v->revisar('buscar() normaliza el código y devuelve null si no existe', fn() =>
    $c->buscar(' cer-1k ')?->nombre() === 'Cera pura 1 kg' && $c->buscar('XXX-99') === null);
$v->revisar('agregar() rechaza un código repetido (DomainException)', fn() =>
    lanza(DomainException::class, fn() => $c->agregar(new Producto('MIE-500', 'Otra miel', $c->categoria('Miel'), 10.0, 1))));
$v->revisar('ordenadosPorPrecio(): propóleo 38 primero; descendente: cera 120 primero', fn() =>
    $c->ordenadosPorPrecio()[0]->codigo() === 'PRO-30' && $c->ordenadosPorPrecio(false)[0]->codigo() === 'CER-1K');
$v->revisar('deCategoria(Derivados) y disponibles() (5 de 6: la miel de cusi está agotada)', fn() =>
    array_map(fn(Producto $p) => $p->codigo(), $c->deCategoria($c->categoria('Derivados'))) === ['POL-250', 'PRO-30']
    && count($c->disponibles()) === 5);

// --- Páginas ---
$v->servidor(__DIR__ . '/public');
$inicio = $v->pedir('/');
$v->revisar('La página agrupa por categoría, muestra el inventario y escapa "Polen &amp; propóleo"', fn() =>
    $inicio['estado'] === 200 && str_contains($inicio['cuerpo'], '<section id="derivados">')
    && str_contains($inicio['cuerpo'], 'Bs 5.030,00') && str_contains($inicio['cuerpo'], 'Polen &amp; propóleo')
    && str_contains($inicio['cuerpo'], 'class="stock-agotado"'));
$ficha = $v->pedir('/producto.php?codigo=MIE-1K');
$v->revisar('La ficha muestra el precio de feria (Bs 76,50) y un código inexistente da 404', fn() =>
    $ficha['estado'] === 200 && str_contains($ficha['cuerpo'], 'Bs 76,50') && $v->pedir('/producto.php?codigo=NO-EXISTE')['estado'] === 404);
exit($v->fin());
