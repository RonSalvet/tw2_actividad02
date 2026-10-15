<?php
// public/index.php — Catálogo de productos y servicios. Un solo require: el autoload de Composer.
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Apicultores\Catalogo;
use Apicultores\Modelo\Categoria;

$catalogo = Catalogo::desdeArchivo(__DIR__ . '/../datos/catalogo.json');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo · Flor de la Chiquitanía</title>
  <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
<header>
  <h1>Apicultores Flor de la Chiquitanía</h1>
  <p><?= count($catalogo->todos()) ?> artículos · <?= count($catalogo->disponibles()) ?> disponibles ·
     inventario: <strong><?= bs($catalogo->valorInventario()) ?></strong></p>
</header>
<main>
<?php foreach (Categoria::cases() as $categoria): ?>
  <section id="<?= $categoria->slug() ?>">
    <h2><?= e($categoria->value) ?> <small><?= e($categoria->descripcion()) ?></small></h2>
    <ul>
    <?php foreach ($catalogo->productos($categoria) as $p): ?>
      <li><?= e($p->nombre()) ?> — <?= $p->precioFormateado() ?> · <?= e($p->detalle()) ?>
          · docena: <?= bs($p->precioVenta(12)) ?></li>
    <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
  <section id="servicios">
    <h2>Servicios</h2>
    <ul>
    <?php foreach ($catalogo->servicios() as $s): ?>
      <li><?= e($s->nombre()) ?> — <?= $s->precioFormateado() ?> <?= e($s->detalle()) ?></li>
    <?php endforeach; ?>
    </ul>
  </section>
  <p><a href="/api.php">Ver el catálogo en JSON</a></p>
</main>
</body>
</html>
