# Laboratorio 7 — Hoja de trabajo: excepciones, front controller y plantillas

Nombre: ______________________   Fecha: ___/___/2026

```
composer dump-autoload
php verificar.php                                  → 5 de 15 al empezar
php -S 127.0.0.1:8000 -t public public/index.php   ← el último argumento es el ROUTER
```

## Parte A — Una sola puerta de entrada
1. Abre `/`, `/producto/MIE-1K`, `/cotizacion` y `/api/articulos`. ¿Qué archivo PHP atiende **todas** esas URL?
2. Abre `/css/estilo.css`. ¿Qué responde antes del TODO 4? ¿Por qué? Completa el TODO 4.
3. En producción con Nginx (Tecnología Web I), ¿qué directiva cumple la misma función? Escríbela.
4. ¿Qué expresión regular genera el Router para `/producto/{codigo}`? ¿Cómo llega `MIE-1K` al controlador?

## Parte B — Excepciones con significado (TODO 1, 2 y 5)
1. Dibuja la jerarquía: `RuntimeException` → `ErrorAplicacion` → …
2. TODO 1: ¿qué código HTTP corresponde a `Conflicto`? Justifícalo.
3. Antes del TODO 2, envía `curl -i -X POST http://127.0.0.1:8000/`. ¿Qué código devuelve? ¿Cuál debería devolver y con qué cabecera?
4. Antes del TODO 5, cotiza 5 unidades de `CER-1K` (hay 4). Copia lo que muestra la página. ¿Qué dos problemas ves?
5. Después del TODO 5: ¿para qué sirve pasar `$e` como tercer argumento (`$previous`)?

## Parte C — Plantillas con escape automático (TODO 3)
1. Busca `<script>alert(1)</script>` en el catálogo antes del TODO 3. ¿Qué pasa? (Si tu navegador lo bloquea, mira el código fuente.)
2. Completa el TODO 3. ¿Por qué las plantillas ya no necesitan `e()`?
3. ¿Qué es `Crudo` y cuándo es correcto usarlo? ¿Cuándo sería peligroso?
4. ¿Por qué `Plantilla::escapar()` rechaza objetos?

## Parte D — Errores inesperados (TODO 6)
1. Crea un archivo `roto.json` con `[{"tipo": "producto", "codigo": ` y levanta el servidor con
   `CATALOGO_ARCHIVO=roto.json php -S 127.0.0.1:8000 -t public public/index.php` (PowerShell:
   `$env:CATALOGO_ARCHIVO="roto.json"`). ¿Qué ve el usuario antes del TODO 6?
2. ¿Qué información útil para un atacante aparece?
3. Completa el TODO 6. ¿Qué ve ahora el usuario y qué queda en `datos/errores.log`?

## Parte E — Verificación
`php verificar.php` → objetivo **15 de 15**.

## Reflexión (preparación de la Validación 1)
Explica con un ejemplo del laboratorio la diferencia entre un error **previsto** (4xx) y uno **inesperado** (5xx), y quién
debe enterarse de cada uno (usuario, desarrollador, soporte).
