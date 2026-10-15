# Tecnología Web II — Laboratorios 5 al 9 y Actividad 2

**Estudiante:** Ronald Salvatierra Flores · **Asignatura:** SIS-0151 Tecnología Web II
**Universidad Privada Domingo Savio** · Ingeniería de Sistemas · Semestre 2/2026
**Docente:** M.Sc. Ing. Jimmy Nataniel Requena Llorentty

Aplicación web de la **Asociación de Apicultores Flor de la Chiquitanía** (catálogo de productos y socios),
migrada de archivos JSON a **MySQL 8.4 con PDO y consultas preparadas**.

## Actividad 2

> Migra la aplicación a MySQL y completa el CRUD de productos y socios con PDO y consultas preparadas,
> sin ninguna consulta vulnerable a inyección SQL.

| Requisito | Dónde está |
|---|---|
| Modelo entidad-relación (socios 1 : N productos) | [`docs/er.png`](docs/er.png) |
| Script SQL con tablas, claves foráneas, restricciones y datos de ejemplo | [`actividad02/sql/`](actividad02/sql) |
| Conexión PDO configurada fuera del código público (variables de entorno) | [`actividad02/config/bd.php`](actividad02/config/bd.php), [`actividad02/src/BaseDatos/Conexion.php`](actividad02/src/BaseDatos/Conexion.php) |
| `ProductoRepositorio` y `SocioRepositorio` con CRUD preparado, validación y paginación | [`actividad02/src/Repositorios/`](actividad02/src/Repositorios), [`actividad02/src/Validacion/`](actividad02/src/Validacion) |
| Demostración de resistencia a la inyección SQL | [`actividad02/demo_inyeccion.php`](actividad02/demo_inyeccion.php), [`actividad02/verificar_actividad2.php`](actividad02/verificar_actividad2.php) |
| Informe técnico (APA 7) | [`docs/Informe_Tecnico_Actividad02.pdf`](docs/Informe_Tecnico_Actividad02.pdf) |
| Bitácora de IA (APA 7) | [`docs/Bitacora_IA_Actividad02.pdf`](docs/Bitacora_IA_Actividad02.pdf) |

## Estructura

```
tw2_actividad02/
├── actividad02/        Aplicación final: MySQL + PDO + repositorios + CRUD de productos y socios
├── laboratorios/       Laboratorios 5 al 9 (carpeta inicio de cada uno, con los TODO resueltos)
│   ├── lab05/          Objetos y clases en PHP
│   ├── lab06/          Herencia, interfaces y Composer
│   ├── lab07/          Excepciones, front controller y plantillas
│   ├── lab08/          MySQL 8.4 con PDO (consultas preparadas)
│   └── lab09/          Repositorio, paginación, filtros y orden seguro
└── docs/               Informe, bitácora de IA, guías completadas, diagrama ER y evidencias
```

## Cómo ejecutar la aplicación (Docker Desktop)

Requisitos: Docker Desktop con el motor en marcha. Desde PowerShell, dentro de `actividad02`:

```powershell
docker compose up -d --build --wait --wait-timeout 180
docker compose exec php composer dump-autoload
docker compose exec -T php php verificar.php               # laboratorio 9: 14 de 14
docker compose exec -T php php verificar_actividad2.php    # actividad 2: 15 de 15
docker compose exec -T php php demo_inyeccion.php          # demostración de inyección SQL
```

Sitio: <http://127.0.0.1:8000/productos> y <http://127.0.0.1:8000/socios>.
Consola MySQL: `docker compose exec mysql mysql -u apicultores -pmiel2026 apicultores`.

Todos los laboratorios usan el puerto 8000: antes de iniciar uno, detén el anterior con `docker compose down`
desde su carpeta. **No uses `down -v`** salvo que quieras borrar la base de datos.

> Las claves de `docker-compose.yml` son de **desarrollo**. En producción deben proveerse como secretos del entorno.

## Seguridad: inyección SQL

- Todas las consultas usan marcadores (`?` o `:nombre`); los datos nunca se concatenan en el SQL.
- El orden usa una **lista permitida** (un nombre de columna no puede ir en un marcador).
- `LIMIT` y `OFFSET` se enlazan con `bindValue(..., PDO::PARAM_INT)` y la página se normaliza.
- Los comodines de `LIKE` (`%`, `_`, `\`) se escapan.
- `verificar_actividad2.php` envía nueve cargas de ataque por cada entrada y analiza el código fuente; una
  prueba de mutación (consulta vulnerable) hace fallar el verificador, como debe.

## Autoría y uso de IA

Código y documentos elaborados por el estudiante con la ayuda del asistente Claude (Anthropic); el detalle
está en la [bitácora de IA](docs/Bitacora_IA_Actividad02.pdf).
