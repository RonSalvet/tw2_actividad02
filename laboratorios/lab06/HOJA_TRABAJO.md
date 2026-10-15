# Laboratorio 6 — Hoja de trabajo: Composer, herencia, interfaces, traits y enums

Nombre: ______________________   Fecha: ___/___/2026

Primero: `composer dump-autoload` (crea `vendor/autoload.php`). Luego `php demo.php` y `php verificar.php` (14 pruebas).

## Parte A — Composer y PSR-4
1. Ejecuta `php verificar.php` **antes** de `composer dump-autoload`. ¿Qué dice?
2. Abre `vendor/composer/autoload_psr4.php`. ¿Qué relación hay entre `Apicultores\` y `src/`?
3. Según PSR-4, ¿en qué archivo debe estar la clase `Apicultores\Modelo\Servicio`? ¿Y `Apicultores\Catalogo`?
4. Prueba `php -r 'require "vendor/autoload.php"; new Apicultores\Modelo\Productos("MIE-500","x",1.0,1,Apicultores\Modelo\Categoria::Miel);'`. Copia el error. ¿Por qué ocurre?
5. ¿Para qué sirve `"files": ["src/funciones.php"]` en `composer.json`? ¿Por qué no se usa PSR-4 para ese archivo?
6. TODO 6 (`public/api.php`): reemplaza los `require` manuales por el autoload. ¿Qué problema tendrían los `require` con 40 clases?

## Parte B — Enum (TODO 1 en `src/Modelo/Categoria.php`)
1. Completa `descripcion()` con `match ($this)`.
2. ¿Qué devuelven `Categoria::from('Hongos')` y `Categoria::tryFrom('Hongos')`? ¿Cuándo usarías cada uno?
3. ¿Qué ventaja tiene el enum frente a la constante `CATEGORIAS = ['Miel', 'Derivados', 'Cera']` del lab 3?

## Parte C — Herencia y clase abstracta (TODO 2 y 3 en `src/Modelo/Producto.php`)
1. ¿Qué hereda `Producto` de `Articulo`? ¿Qué agrega?
2. ¿Qué pasa con `new Articulo('ABC-10', 'x', 1.0)` (paso 7 de la demo)? ¿Por qué tiene sentido?
3. Completa el precio mayorista (TODO 2) y `jsonSerialize` con `parent::` (TODO 3). Antes del TODO 3, ¿qué campos
   faltaban en `/api.php`?
4. ¿Por qué `parent::__construct(...)` va al inicio del constructor de `Producto`?

## Parte D — Interfaz y polimorfismo (TODO 4 y 5)
1. ¿Qué exige la interfaz `Vendible`? ¿Qué clases la cumplen?
2. Completa `Servicio::estaDisponible()` (TODO 4) y `Catalogo::servicios()` (TODO 5).
3. Paso 4 de la demo: ¿por qué 12 unidades de miel cuestan 486 pero 12 personas en el taller cuestan 2400?
   ¿Qué método se ejecuta en cada caso y quién lo decide?
4. `Catalogo::agregar(Vendible $articulo)`: ¿qué pasa si le pasas un `new DateTime()`?

## Parte E — Trait
1. ¿Qué aporta `ConBolivianos` a `Articulo`? ¿Por qué `enBolivianos()` es `protected`?
2. ¿En qué se diferencia un trait de una clase padre?

## Parte F — Verificación
`php verificar.php` → objetivo **14 de 14**. Abre `http://127.0.0.1:8000/` y `http://127.0.0.1:8000/api.php`.

## Reflexión
Dibuja el diagrama de clases (Articulo, Producto, Servicio, Vendible, ConBolivianos, Categoria, Catalogo) con sus
relaciones: hereda, implementa, usa, contiene.
