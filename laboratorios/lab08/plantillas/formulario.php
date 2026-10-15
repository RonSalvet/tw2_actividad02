<?php /** plantillas/formulario.php — Alta y edición de un producto. Con 422 vuelve con lo escrito y los errores por campo. */ ?>
<h2><?= $titulo ?></h2>
<?php if ($errores !== []): ?><p class="alerta" role="alert">Revisa los datos marcados.</p><?php endif; ?>
<form method="post" action="<?= $accion ?>" novalidate>
  <p>
    <label for="codigo">Código</label>
    <input id="codigo" name="codigo" value="<?= $v['codigo'] ?>"<?= $edicion ? ' readonly' : '' ?>>
    <?php if (isset($errores['codigo'])): ?><span class="error"><?= $errores['codigo'] ?></span><?php endif; ?>
  </p>
  <p>
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" value="<?= $v['nombre'] ?>">
    <?php if (isset($errores['nombre'])): ?><span class="error"><?= $errores['nombre'] ?></span><?php endif; ?>
  </p>
  <p>
    <label for="categoria">Categoría</label>
    <select id="categoria" name="categoria">
      <option value="">— elige —</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c ?>"<?= $v['categoria'] === $c ? ' selected' : '' ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errores['categoria'])): ?><span class="error"><?= $errores['categoria'] ?></span><?php endif; ?>
  </p>
  <p>
    <label for="precio">Precio (Bs)</label>
    <input id="precio" name="precio" inputmode="decimal" value="<?= $v['precio'] ?>">
    <?php if (isset($errores['precio'])): ?><span class="error"><?= $errores['precio'] ?></span><?php endif; ?>
  </p>
  <p>
    <label for="stock">Stock</label>
    <input id="stock" name="stock" inputmode="numeric" value="<?= $v['stock'] ?>">
    <?php if (isset($errores['stock'])): ?><span class="error"><?= $errores['stock'] ?></span><?php endif; ?>
  </p>
  <p>
    <label for="socio_id">Socio</label>
    <select id="socio_id" name="socio_id">
      <option value="">— elige —</option>
      <?php foreach ($socios as $s): ?>
        <option value="<?= $s['id'] ?>"<?= $v['socio_id'] === $s['id'] ? ' selected' : '' ?>><?= $s['nombre'] ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errores['socio_id'])): ?><span class="error"><?= $errores['socio_id'] ?></span><?php endif; ?>
  </p>
  <button type="submit">Guardar</button> <a href="/productos">Cancelar</a>
</form>
