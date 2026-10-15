<?php
// src/cargar.php — Carga las clases y las funciones de ayuda. (En la clase 6, Composer lo hará automáticamente.)
declare(strict_types=1);

require_once __DIR__ . '/Categoria.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Catalogo.php';

const ARCHIVO_PRODUCTOS = __DIR__ . '/../datos/productos.json';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function bs(float $monto): string
{
    return 'Bs ' . number_format($monto, 2, ',', '.');
}
