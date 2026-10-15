<?php /** plantillas/socios.php — Lista paginada de socios con búsqueda. Todas las variables llegan escapadas. */ ?>
<?php if ($mensaje !== ''): ?><p class="exito" role="status"><?= $mensaje ?></p><?php endif; ?>
<h2>Socios</h2>
<form method="get" action="/socios" role="search" class="filtros">
  <label for="q">Buscar por nombre o comunidad</label>
  <input id="q" name="q" type="search" value="<?= $q ?>">
  <button type="submit">Buscar</button>
</form>
<table>
  <caption><?= $rango ?></caption>
  <thead><tr><th scope="col">Socio</th><th scope="col">Comunidad</th><th scope="col">Teléfono</th><th scope="col">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($socios as $s): ?>
    <tr>
      <td><a href="/socios/<?= $s['id'] ?>"><?= $s['nombre'] ?></a></td>
      <td><?= $s['comunidad'] ?></td>
      <td><?= $s['telefono'] ?></td>
      <td>
        <a href="/socios/<?= $s['id'] ?>/editar">Editar</a>
        <form method="post" action="/socios/<?= $s['id'] ?>/eliminar" class="en-linea"><button type="submit">Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<nav aria-label="Paginación" class="paginacion">
  <?php if ($anterior !== null): ?><a href="<?= $anterior ?>" rel="prev">← Anterior</a><?php endif; ?>
  <span aria-current="page">Página <?= $numero ?> de <?= $totalPaginas ?></span>
  <?php if ($siguiente !== null): ?><a href="<?= $siguiente ?>" rel="next">Siguiente →</a><?php endif; ?>
</nav>
