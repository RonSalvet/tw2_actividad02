<?php
// public/api.php — El catálogo en JSON (anticipo de la API REST de la clase 13): json_encode usa jsonSerialize().
declare(strict_types=1);
// TODO 6: UNA sola línea, el autoload de Composer, en lugar de 7 require manuales (igual que index.php).
require __DIR__ . '/../vendor/autoload.php';

use Apicultores\Catalogo;

$catalogo = Catalogo::desdeArchivo(__DIR__ . '/../datos/catalogo.json');
header('Content-Type: application/json; charset=utf-8');
echo json_encode($catalogo->todos(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
