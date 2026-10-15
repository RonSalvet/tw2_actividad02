<?php
// config/bd.php — Datos de conexión. Se leen de variables de ENTORNO: nunca se escriben claves en el código.
// Dentro de Docker las define docker-compose.yml (BD_HOST=mysql). Los valores por defecto sirven solo para pruebas locales.
declare(strict_types=1);

return [
    'host'    => getenv('BD_HOST') ?: '127.0.0.1',
    'puerto'  => (int) (getenv('BD_PUERTO') ?: 3306),
    'nombre'  => getenv('BD_NOMBRE') ?: 'apicultores',
    'usuario' => getenv('BD_USUARIO') ?: 'apicultores',
    'clave'   => getenv('BD_CLAVE') ?: 'miel2026',
];
