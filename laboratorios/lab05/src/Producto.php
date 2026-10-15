<?php
// src/Producto.php — Un producto de la asociación. Los DATOS y las REGLAS viven juntos (encapsulamiento).
declare(strict_types=1);

final class Producto
{
    public const FORMATO_CODIGO = '/^[A-Z]{3}-[A-Z0-9]{2,4}$/';
    public const STOCK_BAJO = 5;

    // TODO 3 (encapsulamiento): ¿qué pasa si alguien escribe $p->stock = -50 desde afuera?
    //         Cambia la visibilidad para que el stock SOLO cambie con vender() y reponer().
    private int $stock;

    public function __construct(
        private readonly string $codigo,
        private string $nombre,
        private readonly Categoria $categoria,
        private float $precio,
        int $stock,
        private readonly string $socio = '',
    ) {
        // TODO 1: un objeto Producto NUNCA debe existir en un estado inválido. Lanza InvalidArgumentException si:
        //         el código no cumple self::FORMATO_CODIGO, el precio es <= 0 o el stock es negativo.
        if (!preg_match(self::FORMATO_CODIGO, $codigo)) {
            throw new InvalidArgumentException("Código inválido: $codigo");
        }
        if ($precio <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor que cero.');
        }
        if ($stock < 0) {
            throw new InvalidArgumentException('El stock no puede ser negativo.');
        }
        $this->stock = $stock;
    }

    public function codigo(): string { return $this->codigo; }
    public function nombre(): string { return $this->nombre; }
    public function categoria(): Categoria { return $this->categoria; }
    public function precio(): float { return $this->precio; }
    public function stock(): int { return $this->stock; }
    public function socio(): string { return $this->socio; }

    public function estaDisponible(): bool
    {
        return $this->stock > 0;
    }

    public function estadoStock(): string
    {
        return match (true) {
            $this->stock === 0 => 'agotado',
            $this->stock <= self::STOCK_BAJO => 'bajo',
            default => 'normal',
        };
    }

    public function precioConDescuento(float $porcentaje): float
    {
        if ($porcentaje < 0 || $porcentaje > 50) {
            throw new InvalidArgumentException('El descuento debe estar entre 0 % y 50 %.');
        }
        return round($this->precio * (1 - $porcentaje / 100), 2);
    }

    public function valorEnStock(): float
    {
        return $this->precio * $this->stock;
    }

    /** Registra una venta: no se puede vender más de lo que hay. */
    public function vender(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser positiva.');
        }
        // TODO 2: si se pide más de lo que hay, lanza DomainException("Stock insuficiente de …: hay …, se pidieron ….")
        if ($cantidad > $this->stock) {
            throw new DomainException(
                "Stock insuficiente de {$this->codigo}: hay {$this->stock}, se pidieron $cantidad."
            );
        }
        $this->stock -= $cantidad;
    }

    public function reponer(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser positiva.');
        }
        $this->stock += $cantidad;
    }

    public function cambiarPrecio(float $nuevo): void
    {
        if ($nuevo <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor que cero.');
        }
        $this->precio = $nuevo;
    }

    /** Para guardar en JSON (lo inverso de Catalogo::desdeArchivo). */
    public function aArreglo(): array
    {
        return [
            'codigo' => $this->codigo, 'nombre' => $this->nombre, 'categoria' => $this->categoria->nombre(),
            'precio' => $this->precio, 'stock' => $this->stock, 'socio' => $this->socio,
        ];
    }
}
