<?php
// src/Categoria.php — Una categoría del catálogo (Miel, Derivados, Cera).
declare(strict_types=1);

final class Categoria
{
    // Promoción de propiedades en el constructor (PHP 8): declara y asigna en una sola línea
    public function __construct(
        private readonly string $nombre,
        private readonly string $descripcion = '',
    ) {
        if (trim($nombre) === '') {
            throw new InvalidArgumentException('La categoría necesita un nombre.');
        }
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    /** "Derivados de la colmena" → "derivados-de-la-colmena" (para anclas y URL) */
    public function slug(): string
    {
        $sinTildes = strtr(mb_strtolower($this->nombre), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $sinTildes), '-');
    }
}
