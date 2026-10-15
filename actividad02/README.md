# Actividad 2 — Catálogo de apicultores con MySQL + PDO

CRUD de **productos** y **socios** con repositorios (`ProductoRepositorio`, `SocioRepositorio`), consultas
preparadas, validación en el servidor, paginación, filtros y orden con lista permitida.

```powershell
docker compose up -d --build --wait --wait-timeout 180
docker compose exec php composer dump-autoload
docker compose exec -T php php verificar_actividad2.php
```

| Ruta | Acción |
|---|---|
| `/productos` | Lista paginada con búsqueda, categoría y orden |
| `/productos/nuevo` · `/productos/{codigo}/editar` | Registrar / editar producto |
| `/socios` | Lista paginada de socios con búsqueda |
| `/socios/nuevo` · `/socios/{id}/editar` · `/socios/{id}` | Registrar / editar / ficha con sus productos |

Ver el [README principal](../README.md) para la estructura completa del repositorio.
