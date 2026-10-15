<?php /** plantillas/formulario_socio.php — Registrar y editar socios (mismo formulario). Variables escapadas. */
$campo = function (string $nombre, string $etiqueta, string $extra = '') use ($datos, $errores): void {
    $error = $errores[$nombre] ?? null;
    echo "<p><label for=\"f-$nombre\">$etiqueta</label>"
        . "<input id=\"f-$nombre\" name=\"$nombre\" value=\"{$datos[$nombre]}\" $extra"
        . ($error ? " aria-invalid=\"true\" aria-describedby=\"f-$nombre-error\">" : '>')
        . ($error ? "<span class=\"error\" id=\"f-$nombre-error\">$error</span>" : '') . '</p>';
};
?>
<p><a href="/socios">← Socios</a></p>
<h2><?= $nuevo ? 'Registrar socio' : "Editar a {$actual}" ?></h2>
<?php if ($errores !== []): ?><p class="alerta" role="alert"><?= $errores['general'] ?? 'Revisa los campos marcados.' ?></p><?php endif; ?>
<form method="post" action="<?= $accion ?>" novalidate>
  <?php $campo('nombre', 'Nombre', 'maxlength="80" required'); ?>
  <?php $campo('comunidad', 'Comunidad', 'maxlength="60" required'); ?>
  <?php $campo('telefono', 'Teléfono (opcional, solo dígitos)', 'inputmode="numeric" maxlength="12"'); ?>
  <button type="submit"><?= $nuevo ? 'Registrar' : 'Guardar cambios' ?></button>
</form>
