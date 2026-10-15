<?php
// public/producto.php?codigo=MIE-500 — Ficha del producto con precio de feria (10 % de descuento).
declare(strict_types=1);
require __DIR__ . '/../src/cargar.php';

$catalogo = Catalogo::desdeArchivo(ARCHIVO_PRODUCTOS);
$p = $catalogo->buscar((string) ($_GET['codigo'] ?? ''));
if ($p === null) {
    http_response_code(404);
    exit('Producto no encontrado');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($p->nombre()) ?> · Flor de la Chiquitanía</title>
  <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
<main>
  <p><a href="/#<?= e($p->categoria()->slug()) ?>">← <?= e($p->categoria()->nombre()) ?></a></p>
  <h1><?= e($p->nombre()) ?></h1>
  <dl>
    <dt>Código</dt><dd><?= e($p->codigo()) ?></dd>
    <dt>Precio</dt><dd><?= bs($p->precio()) ?></dd>
    <dt>Precio de feria (−10 %)</dt><dd><?= bs($p->precioConDescuento(10)) ?></dd>
    <dt>Stock</dt><dd><?= $p->stock() ?> unidades (<?= $p->estadoStock() ?>)</dd>
    <dt>Valor en stock</dt><dd><?= bs($p->valorEnStock()) ?></dd>
    <dt>Socio productor</dt><dd><?= e($p->socio()) ?></dd>
  </dl>
</main>
</body>
</html>
