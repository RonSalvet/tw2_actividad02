# Laboratorio 5 — Hoja de trabajo: el catálogo con objetos (cierre de la Actividad 1)

Nombre: ______________________   Fecha: ___/___/2026

Comandos: `php demo.php` (recorrido por consola) · `php verificar.php` (14 pruebas) · `php -S 127.0.0.1:8000 -t public`.

## Parte A — Antes de tocar nada
Ejecuta `php demo.php` **antes** de completar los TODO y anota la salida.
1. ¿Qué pasó en el paso 4 (vender 5 cuando quedaban 2)? ¿Cuánto stock quedó realmente? (Pista: `var_dump($p->stock())`.)
2. ¿Qué pasó en los pasos 5 y 6? ¿Apareció algún mensaje?
3. ¿Cuánto vale el inventario (paso 9)? ¿Es correcto?
4. En tus palabras: ¿qué problemas tendría la asociación si el sistema funciona así?

## Parte B — Un objeto que se defiende solo (TODO 1, 2 y 3 en `src/Producto.php`)
1. Completa los TODO y vuelve a ejecutar `php demo.php`. Copia las líneas 4, 5 y 6.
2. ¿Por qué validar en el **constructor** y no en cada página que crea productos?
3. ¿Qué diferencia hay entre `private`, `private readonly` y `public`? Da un ejemplo de cada uno en `Producto`.
4. ¿Por qué se usan dos excepciones distintas (`InvalidArgumentException` y `DomainException`)?

## Parte C — El catálogo (TODO 4, 5 y 6 en `src/Catalogo.php`)
1. Antes del TODO 4, ejecuta:
   `php -r 'require "src/cargar.php"; $c=Catalogo::desdeArchivo("datos/productos.json"); $c->agregar(new Producto("MIE-500","Otra miel",$c->categoria("Miel"),10.0,1)); echo $c->cantidad(), " ", $c->buscar("MIE-500")->nombre();'`
   ¿Qué pasó con la miel original?
2. Completa los TODO. ¿Cuál es el valor del inventario? ¿Coincide con el del laboratorio 2?
3. ¿Qué devuelve `$a <=> $b` en cada caso (menor, igual, mayor)?
4. ¿Por qué `desdeArchivo` es un método `static`?

## Parte D — Objetos y referencias (pasos 7 y 8 de la demo)
1. Después de `$otra = $p; $otra->reponer(10);`, ¿por qué cambió también `$p`?
2. ¿Qué hace `clone`? ¿Cuándo lo usarías?

## Parte E — Verificación
`php verificar.php` → objetivo **14 de 14**. Abre `http://127.0.0.1:8000/` y la ficha de un producto.

## Parte F — Entrega integrada de la Actividad 1 (labs 1 a 5)
Completa la tabla con lo que aprendiste en cada laboratorio y cómo lo usa el catálogo con objetos:

| Lab | Concepto principal | ¿Dónde aparece en el lab 5? |
|---|---|---|
| 1 | | |
| 2 | | |
| 3 | | |
| 4 | | |
| 5 | | |

## Reflexión
Compara `valor_inventario()` del laboratorio 2 con `Catalogo::valorInventario()`. ¿Qué ganaste al pasar a objetos?
