<?php
// src/Catalogo.php — Colección de categorías y productos: reemplaza a las funciones sueltas de los labs 2 a 4.
declare(strict_types=1);

final class Catalogo
{
    /** @var array<string, Categoria> clave: nombre */
    private array $categorias = [];
    /** @var array<string, Producto> clave: código */
    private array $productos = [];

    /** Método estático "fábrica": crea un catálogo a partir del archivo JSON. */
    public static function desdeArchivo(string $ruta): self
    {
        $catalogo = new self();
        foreach (['Miel' => 'Miel pura de la Chiquitanía', 'Derivados' => 'Polen, propóleo y más', 'Cera' => 'Cera de abeja'] as $n => $d) {
            $catalogo->agregarCategoria(new Categoria($n, $d));
        }
        $filas = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        foreach ($filas as $f) {
            $catalogo->agregar(new Producto(
                $f['codigo'], $f['nombre'], $catalogo->categoria($f['categoria']),
                (float) $f['precio'], (int) $f['stock'], $f['socio'] ?? '',
            ));
        }
        return $catalogo;
    }

    public function guardarEn(string $ruta): void
    {
        $filas = array_map(fn(Producto $p): array => $p->aArreglo(), array_values($this->productos));
        file_put_contents($ruta, json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR) . PHP_EOL, LOCK_EX);
    }

    public function agregarCategoria(Categoria $categoria): void
    {
        $this->categorias[$categoria->nombre()] = $categoria;
    }

    public function categoria(string $nombre): Categoria
    {
        return $this->categorias[$nombre] ?? throw new InvalidArgumentException("No existe la categoría $nombre.");
    }

    /** @return Categoria[] */
    public function categorias(): array
    {
        return array_values($this->categorias);
    }

    public function agregar(Producto $producto): void
    {
        // TODO 4: si ya existe un producto con ese código, lanza DomainException (¿qué pasaría si no?)
        if (isset($this->productos[$producto->codigo()])) {
            throw new DomainException(
                "Ya existe un producto con el código {$producto->codigo()}."
            );
        }
        $this->productos[$producto->codigo()] = $producto;
    }

    public function buscar(string $codigo): ?Producto
    {
        return $this->productos[strtoupper(trim($codigo))] ?? null;
    }

    /** @return Producto[] */
    public function todos(): array
    {
        return array_values($this->productos);
    }

    /** @return Producto[] */
    public function deCategoria(Categoria $categoria): array
    {
        return array_values(array_filter($this->productos, fn(Producto $p): bool => $p->categoria() === $categoria));
    }

    /** @return Producto[] */
    public function disponibles(): array
    {
        return array_values(array_filter($this->productos, fn(Producto $p): bool => $p->estaDisponible()));
    }

    /** @return Producto[] */
    public function ordenadosPorPrecio(bool $ascendente = true): array
    {
        $lista = array_values($this->productos);
        // TODO 5: ordena $lista con usort y el operador nave espacial (<=>) usando $a->precio() y $b->precio();
        //         si $ascendente es false, al revés.
        usort($lista, fn(Producto $a, Producto $b): int => $ascendente
            ? $a->precio() <=> $b->precio()
            : $b->precio() <=> $a->precio());
        return $lista;
    }

    public function valorInventario(): float
    {
        // TODO 6: suma el valorEnStock() de cada producto (array_map + array_sum). Debe dar 5030.
        return array_sum(array_map(
            fn(Producto $p): float => $p->valorEnStock(),
            $this->productos
        ));
    }

    public function cantidad(): int
    {
        return count($this->productos);
    }
}
