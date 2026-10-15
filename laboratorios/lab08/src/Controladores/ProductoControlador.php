<?php
// src/Controladores/ProductoControlador.php — Recibe la petición, valida, usa ProductoConsultas y elige la vista. Nada de HTML ni SQL aquí.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\BaseDatos\ProductoConsultas;
use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Vista\Plantilla;

final class ProductoControlador
{
    public function __construct(private readonly ProductoConsultas $productos, private readonly Plantilla $vista)
    {
    }

    /** GET /productos?q=miel — lista (o busca) productos. */
    public function listar(): string
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $filas = $q === '' ? $this->productos->todos() : $this->productos->buscar($q);
        return $this->vista->pagina('productos', [
            'q' => $q,
            'productos' => array_map($this->fila(...), $filas),
            'total' => count($filas),
            'inventario' => bs($this->productos->valorInventario()),
            'aviso' => (string) ($_GET['aviso'] ?? ''),
        ], 'Productos');
    }

    /** GET /productos/nuevo */
    public function nuevo(): string
    {
        return $this->formulario('Nuevo producto', '/productos', ['stock' => '0'], []);
    }

    /** POST /productos — 303 si se guardó, 422 si los datos no son válidos. */
    public function crear(): string
    {
        $entrada = $this->entrada();
        $errores = $this->validar($entrada, true);
        if ($errores !== []) {
            return $this->rechazar('Nuevo producto', '/productos', $entrada, $errores);
        }
        $this->productos->insertar($this->datos($entrada));      // puede lanzar Conflicto (409)
        return $this->redirigir('/productos?aviso=' . rawurlencode("Producto {$entrada['codigo']} registrado."));
    }

    /** GET /productos/{codigo}/editar */
    public function editar(string $codigo): string
    {
        $p = $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        return $this->formulario("Editar {$p['codigo']}", "/productos/{$p['codigo']}/editar", [
            'codigo' => $p['codigo'], 'nombre' => $p['nombre'], 'categoria' => $p['categoria'],
            'precio' => (string) $p['precio'], 'stock' => (string) $p['stock'], 'socio_id' => (string) $p['socio_id'],
        ], [], true);
    }

    /** POST /productos/{codigo}/editar */
    public function actualizar(string $codigo): string
    {
        $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        $entrada = $this->entrada();
        $entrada['codigo'] = $codigo;                           // el código no se cambia al editar
        $errores = $this->validar($entrada, false);
        if ($errores !== []) {
            return $this->rechazar("Editar $codigo", "/productos/$codigo/editar", $entrada, $errores, true);
        }
        $this->productos->actualizar($codigo, $this->datos($entrada));
        return $this->redirigir('/productos?aviso=' . rawurlencode("Producto $codigo actualizado."));
    }

    /** POST /productos/{codigo}/eliminar */
    public function eliminar(string $codigo): string
    {
        $this->productos->eliminar($codigo);                    // NoEncontrado (404) si no existe
        return $this->redirigir('/productos?aviso=' . rawurlencode("Producto $codigo eliminado."));
    }

    // ---------------------------------------------------------------- apoyo

    /** @return array<string, string> lo que escribió la persona, como texto */
    private function entrada(): array
    {
        $r = [];
        foreach (['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id'] as $campo) {
            $r[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        $r['codigo'] = strtoupper($r['codigo']);
        return $r;
    }

    /** @return array<string, string> errores por campo (vacío = todo bien) */
    private function validar(array $d, bool $esNuevo): array
    {
        $e = [];
        if ($esNuevo) {
            if (!preg_match('/^[A-Z]{3}-[A-Z0-9]{2,4}$/', $d['codigo'])) {
                $e['codigo'] = 'Formato AAA-XX (3 letras, guion y 2 a 4 letras o números).';
            } elseif ($this->productos->porCodigo($d['codigo']) !== null) {
                $e['codigo'] = "Ya existe un producto con el código {$d['codigo']}.";
            }
        }
        if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 120) {
            $e['nombre'] = 'Escribe un nombre de hasta 120 caracteres.';
        }
        if (Categoria::tryFrom($d['categoria']) === null) {
            $e['categoria'] = 'Elige Miel, Derivados o Cera.';
        }
        if (!preg_match('/^\d{1,8}([.,]\d{1,2})?$/', $d['precio']) || (float) str_replace(',', '.', $d['precio']) <= 0) {
            $e['precio'] = 'El precio debe ser mayor que cero (máximo 2 decimales).';
        }
        if (filter_var($d['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]) === false) {
            $e['stock'] = 'El stock es un número entero desde 0.';
        }
        $socio = filter_var($d['socio_id'], FILTER_VALIDATE_INT);
        if ($socio === false || !$this->productos->existeSocio($socio)) {
            $e['socio_id'] = 'Elige un socio de la lista.';
        }
        return $e;
    }

    /** @return array{codigo:string,nombre:string,categoria:string,precio:string,stock:int,socio_id:int} */
    private function datos(array $d): array
    {
        return [
            'codigo' => $d['codigo'], 'nombre' => $d['nombre'], 'categoria' => $d['categoria'],
            'precio' => number_format((float) str_replace(',', '.', $d['precio']), 2, '.', ''),
            'stock' => (int) $d['stock'], 'socio_id' => (int) $d['socio_id'],
        ];
    }

    private function rechazar(string $titulo, string $accion, array $entrada, array $errores, bool $edicion = false): string
    {
        http_response_code((new DatosInvalidos($errores))->codigoHttp());   // 422
        return $this->formulario($titulo, $accion, $entrada, $errores, $edicion);
    }

    private function formulario(string $titulo, string $accion, array $valores, array $errores, bool $edicion = false): string
    {
        return $this->vista->pagina('formulario', [
            'titulo' => $titulo,
            'accion' => $accion,
            'edicion' => $edicion,
            'v' => $valores + ['codigo' => '', 'nombre' => '', 'categoria' => '', 'precio' => '', 'stock' => '0', 'socio_id' => ''],
            'errores' => $errores,
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'socios' => array_map(fn(array $s): array => ['id' => (string) $s['id'], 'nombre' => $s['nombre']], $this->productos->socios()),
        ], $titulo);
    }

    /** Después de guardar se REDIRIGE (303): así recargar la página no repite el envío del formulario. */
    private function redirigir(string $ruta): string
    {
        http_response_code(303);
        header('Location: ' . $ruta);
        return '';
    }

    /** @return array<string, string> datos simples para la vista (la vista no recibe objetos ni números sin formato) */
    private function fila(array $p): array
    {
        return [
            'codigo' => (string) $p['codigo'], 'nombre' => (string) $p['nombre'], 'categoria' => (string) $p['categoria'],
            'precio' => bs((float) $p['precio']), 'stock' => (string) $p['stock'], 'socio' => (string) $p['socio'],
            'estado' => (int) $p['stock'] === 0 ? 'Agotado' : ((int) $p['stock'] <= 5 ? 'Últimas unidades' : 'Disponible'),
        ];
    }
}
