<?php
// public/index.php — Catálogo agrupado por categoría, construido con OBJETOS.
declare(strict_types=1);
require __DIR__ . '/../src/cargar.php';

$catalogo = Catalogo::desdeArchivo(ARCHIVO_PRODUCTOS);
$etiquetas = ['agotado' => 'Agotado', 'bajo' => 'Últimas unidades', 'normal' => 'Disponible'];
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
  <p><?= $catalogo->cantidad() ?> productos · <?= count($catalogo->disponibles()) ?> disponibles ·
     valor del inventario: <strong><?= bs($catalogo->valorInventario()) ?></strong></p>
</header>
<main>
<?php foreach ($catalogo->categorias() as $categoria): ?>
  <section id="<?= e($categoria->slug()) ?>">
    <h2><?= e($categoria->nombre()) ?> <small><?= e($categoria->descripcion()) ?></small></h2>
    <ul>
    <?php foreach ($catalogo->deCategoria($categoria) as $p): ?>
      <li class="stock-<?= $p->estadoStock() ?>">
        <a href="/producto.php?codigo=<?= urlencode($p->codigo()) ?>"><?= e($p->nombre()) ?></a>
        — <?= bs($p->precio()) ?> · <?= $etiquetas[$p->estadoStock()] ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
</main>
</body>
</html>
