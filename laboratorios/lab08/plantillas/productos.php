<?php /** plantillas/productos.php — TODAS las variables llegan escapadas: aquí no hace falta e(). */ ?>
<h2>Productos</h2>
<?php if ($aviso !== ''): ?><p class="exito" role="status"><?= $aviso ?></p><?php endif; ?>
<form method="get" action="/productos" role="search">
  <label for="q">Buscar por nombre o código</label>
  <input id="q" name="q" type="search" value="<?= $q ?>">
  <button type="submit">Buscar</button>
</form>
<?php if ($q !== ''): ?><p>Resultados para «<?= $q ?>»: <?= $total ?></p><?php endif; ?>
<p>Valor del inventario: <strong><?= $inventario ?></strong></p>
<table>
  <thead><tr><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col">Socio</th><th scope="col">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($productos as $p): ?>
    <tr>
      <td><?= $p['codigo'] ?></td>
      <td><?= $p['nombre'] ?></td>
      <td><?= $p['categoria'] ?></td>
      <td class="num"><?= $p['precio'] ?></td>
      <td class="num"><?= $p['stock'] ?> <small><?= $p['estado'] ?></small></td>
      <td><?= $p['socio'] ?></td>
      <td>
        <a href="/productos/<?= $p['codigo'] ?>/editar">Editar</a>
        <form class="en-linea" method="post" action="/productos/<?= $p['codigo'] ?>/eliminar">
          <button type="submit" aria-label="Eliminar <?= $p['nombre'] ?>">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
