<?php
// src/Controladores/ProductosControlador.php — Usa REPOSITORIOS (interfaces) en lugar de SQL. Pagina, filtra y ordena.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;
use Apicultores\Repositorios\ProductoRepositorio;
use Apicultores\Repositorios\SocioRepositorio;
use Apicultores\Validacion\ValidadorProducto;
use Apicultores\Vista\Plantilla;

final class ProductosControlador
{
    public const POR_PAGINA = 10;

    public function __construct(
        private readonly ProductoRepositorio $productos,
        private readonly SocioRepositorio $socios,
        private readonly ValidadorProducto $validador,
        private readonly Plantilla $vista,
    ) {
    }

    public function listar(): string
    {
        // Todo lo que llega por GET se normaliza: texto, categoría válida o null, orden permitido, página >= 1
        $q = $this->texto('q');
        $categoria = Categoria::tryFrom($this->texto('categoria'));
        $orden = $this->texto('orden');
        if (!array_key_exists($orden, ProductoRepositorio::ORDENES)) {
            $orden = 'nombre';                                          // orden desconocido → el de por defecto
        }
        $bruta = $_GET['pagina'] ?? '1';                                // puede llegar como arreglo (?pagina[]=2)
        $pedida = is_string($bruta)
            ? filter_var($bruta, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]])
            : 1;
        $pedida = min($pedida, 1_000_000);                              // evita OFFSET absurdos; luego se ajusta a la última

        $pagina = $this->productos->buscar($q, $categoria, $orden, $pedida, self::POR_PAGINA);
        if ($pedida > $pagina->totalPaginas()) {              // ?pagina=99 → la última página que exista
            $pagina = $this->productos->buscar($q, $categoria, $orden, $pagina->totalPaginas(), self::POR_PAGINA);
        }

        $filtros = array_filter(['q' => $q, 'categoria' => $categoria?->value, 'orden' => $orden === 'nombre' ? null : $orden]);
        $enlace = fn(int $n): string => '/productos?' . http_build_query($filtros + ['pagina' => $n]);
        return $this->vista->pagina('productos', [
            'q' => $q, 'categoria' => $categoria?->value ?? '', 'orden' => $orden,
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'ordenes' => ProductoRepositorio::ORDENES,
            'productos' => array_map($this->fila(...), $pagina->elementos),
            'rango' => $pagina->rango(), 'numero' => $pagina->numero, 'totalPaginas' => $pagina->totalPaginas(),
            'anterior' => $pagina->tieneAnterior() ? $enlace($pagina->numero - 1) : null,
            'siguiente' => $pagina->tieneSiguiente() ? $enlace($pagina->numero + 1) : null,
            'mensaje' => $this->mensaje(),
        ], 'Productos');
    }

    public function nuevo(): string
    {
        return $this->formulario('/productos', [], [], true);
    }

    public function crear(): never
    {
        $this->productos->guardar($this->validador->validar($_POST, nuevo: true));
        $this->redirigir('/productos?creado=' . urlencode(strtoupper(trim((string) $_POST['codigo']))));
    }

    public function editar(string $codigo): string
    {
        $p = $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        return $this->formulario('/productos/' . rawurlencode($codigo), [
            'codigo' => $p->codigo, 'nombre' => $p->nombre, 'categoria' => $p->categoria->value,
            'precio' => $p->precio, 'stock' => $p->stock, 'socio_id' => $p->socio->id], [], false);
    }

    public function actualizar(string $codigo): never
    {
        $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        $this->productos->guardar($this->validador->validar(['codigo' => $codigo] + $_POST, nuevo: false));
        $this->redirigir('/productos?actualizado=' . urlencode($codigo));
    }

    public function eliminar(string $codigo): never
    {
        $this->productos->eliminar($codigo);
        $this->redirigir('/productos?eliminado=' . urlencode($codigo));
    }

    public function formulario(string $accion, array $datos, array $errores, bool $nuevo): string
    {
        $campos = ['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id'];
        $socios = [];
        foreach ($this->socios->todos() as $s) {
            $socios[$s->id] = $s->nombre;
        }
        return $this->vista->pagina('formulario', [
            'accion' => $accion, 'nuevo' => $nuevo, 'errores' => $errores,
            'datos' => array_map(fn(mixed $v): string => is_scalar($v) ? (string) $v : '', array_intersect_key($datos, array_flip($campos)) + array_fill_keys($campos, '')),
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'socios' => $socios,
        ], $nuevo ? 'Registrar producto' : 'Editar producto');
    }

    private function fila(Producto $p): array
    {
        return ['codigo' => $p->codigo, 'nombre' => $p->nombre, 'categoria' => $p->categoria->value, 'precio_bs' => $p->precioBs(),
                'stock' => $p->stock, 'socio' => $p->socio->nombre, 'socio_id' => $p->socio->id, 'comunidad' => $p->socio->comunidad];
    }

    private function mensaje(): string
    {
        foreach (['creado' => 'registrado', 'actualizado' => 'actualizado', 'eliminado' => 'eliminado'] as $clave => $verbo) {
            if (is_string($_GET[$clave] ?? null)) {
                return "Producto {$_GET[$clave]} $verbo.";
            }
        }
        return '';
    }

    /** Un parámetro GET como texto: si no llega, o llega como arreglo (?q[]=x), es ''. */
    private function texto(string $clave): string
    {
        $valor = $_GET[$clave] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    private function redirigir(string $url): never
    {
        header("Location: $url", true, 303);
        exit;
    }
}
