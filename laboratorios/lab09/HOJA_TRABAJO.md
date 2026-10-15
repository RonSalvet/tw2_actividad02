# Laboratorio 9 — Hoja de trabajo: repositorio, paginación, filtros y socios

Nombre: ______________________   Fecha: ___/___/2026

```
docker compose up -d          # si el MySQL del lab 8 sigue encendido, sirve igual
composer dump-autoload
php verificar.php             # carga 36 productos y 8 socios → 3 de 14 al inicio
php -S 127.0.0.1:8000 -t public public/index.php
```

## Parte A — Del SQL suelto al repositorio
1. Compara `ProductoConsultas` (lab 8) con `ProductoRepositorioMysql`. ¿Qué devuelve cada uno: arreglos u objetos?
2. ¿Qué hace el método privado `hidratar()`?
3. ¿Por qué el controlador recibe un `ProductoRepositorio` (interfaz) y no un `ProductoRepositorioMysql`?
4. Mira `ProductoRepositorioMemoria`. ¿En qué situación real te serviría tener dos implementaciones?

## Parte B — Paginación (TODO 1 y 2)
1. Abre `/productos` antes de los TODO. ¿Cuántas filas muestra? ¿Qué dice la paginación?
2. TODO 1: ¿cuántas páginas hay con 36 productos de 10 en 10? ¿Y con 0 productos? ¿Por qué `ceil` y no `round`?
3. TODO 2: completa `LIMIT :limite OFFSET :desde`. Para la página 3, ¿qué valores reciben `:limite` y `:desde`?
4. ¿Por qué se usa `bindValue(..., PDO::PARAM_INT)` y no se pasan en el arreglo de `execute()`?
5. ¿Para qué sirve la consulta `SELECT COUNT(*)` adicional?

## Parte C — Orden seguro y parámetros de la URL (TODO 3 y 4)
1. Antes de los TODO, abre `/productos?orden=stock_desc` (no está en la lista) y
   `/productos?orden=p.precio%3B%20DROP%20TABLE%20productos`. ¿Qué pasa en cada caso? Mira `datos/errores.log`.
2. ¿Se borró la tabla? (`SELECT COUNT(*) FROM productos;`) ¿Por qué no? ¿Significa que el código era seguro?
3. ¿Por qué no se puede escribir `ORDER BY :orden` con un marcador?
4. TODO 4: ¿qué debe pasar con `?pagina=-5`, `?pagina=abc` y `?pagina=99`?

## Parte D — Filtros y relación 1 a N (TODO 5 y 6)
1. TODO 5: filtra por categoría. ¿Cuántos productos de Cera hay? ¿Y de Cera con "vela"?
2. Revisa los enlaces "Anterior"/"Siguiente" con un filtro activo. ¿Qué hace `http_build_query`? ¿Por qué aparece `&amp;` en el HTML?
3. TODO 6: completa `deSocio()`. Abre `/socios/3`. ¿Qué tipo de relación hay entre socios y productos?

## Parte E — Verificación
`php verificar.php` → objetivo **14 de 14**.

## Reflexión
Si mañana la asociación decide usar PostgreSQL o una API externa en lugar de MySQL, ¿qué archivos cambiarías y cuáles no? Justifica.
