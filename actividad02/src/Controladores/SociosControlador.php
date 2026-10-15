<?php
// src/Controladores/SociosControlador.php — CRUD de socios: listar (con búsqueda y paginación), ver, registrar, editar y eliminar.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Producto;
use Apicultores\Modelo\Socio;
use Apicultores\Repositorios\ProductoRepositorio;
use Apicultores\Repositorios\SocioRepositorio;
use Apicultores\Validacion\ValidadorSocio;
use Apicultores\Vista\Plantilla;

final class SociosControlador
{
    public const POR_PAGINA = 10;

    public function __construct(
        private readonly SocioRepositorio $socios,
        private readonly ProductoRepositorio $productos,
        private readonly ValidadorSocio $validador,
        private readonly Plantilla $vista,
    ) {
    }

    public function listar(): string
    {
        $q = $this->texto('q');
        $bruta = $_GET['pagina'] ?? '1';
        $pedida = is_string($bruta)
            ? filter_var($bruta, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]])
            : 1;
        $pedida = min($pedida, 1_000_000);

        $pagina = $this->socios->buscar($q, $pedida, self::POR_PAGINA);
        if ($pedida > $pagina->totalPaginas()) {
            $pagina = $this->socios->buscar($q, $pagina->totalPaginas(), self::POR_PAGINA);
        }
        $filtros = array_filter(['q' => $q]);
        $enlace = fn(int $n): string => '/socios?' . http_build_query($filtros + ['pagina' => $n]);
        return $this->vista->pagina('socios', [
            'q' => $q,
            'socios' => array_map(fn(Socio $s): array => ['id' => $s->id, 'nombre' => $s->nombre, 'comunidad' => $s->comunidad,
                'telefono' => $s->telefono ?? '—'], $pagina->elementos),
            'rango' => $pagina->rango(), 'numero' => $pagina->numero, 'totalPaginas' => $pagina->totalPaginas(),
            'anterior' => $pagina->tieneAnterior() ? $enlace($pagina->numero - 1) : null,
            'siguiente' => $pagina->tieneSiguiente() ? $enlace($pagina->numero + 1) : null,
            'mensaje' => $this->mensaje(),
        ], 'Socios');
    }

    public function ver(string $id): string
    {
        $socio = $this->buscarSocio($id);
        $productos = $this->productos->deSocio($socio->id);
        return $this->vista->pagina('socio', [
            'id' => $socio->id, 'nombre' => $socio->nombre, 'comunidad' => $socio->comunidad,
            'telefono' => $socio->telefono ?? 'sin teléfono',
            'productos' => array_map(fn(Producto $p): array => ['codigo' => $p->codigo, 'nombre' => $p->nombre,
                'precio_bs' => $p->precioBs(), 'stock' => $p->stock], $productos),
            'valor' => bs(array_sum(array_map(fn(Producto $p): float => (float) $p->precio * $p->stock, $productos))),
        ], $socio->nombre);
    }

    public function nuevo(): string
    {
        return $this->formulario('/socios', [], [], true);
    }

    public function crear(): string
    {
        try {
            $id = $this->socios->guardar($this->validador->validar($_POST));
        } catch (DatosInvalidos $e) {
            http_response_code(422);
            return $this->formulario('/socios', $_POST, $e->errores(), true);
        }
        $this->redirigir('/socios?creado=' . $id);
    }

    public function editar(string $id): string
    {
        $s = $this->buscarSocio($id);
        return $this->formulario('/socios/' . $s->id, ['nombre' => $s->nombre, 'comunidad' => $s->comunidad,
            'telefono' => $s->telefono ?? ''], [], false, $s->nombre);
    }

    public function actualizar(string $id): string
    {
        $s = $this->buscarSocio($id);
        try {
            $this->socios->guardar(['id' => $s->id] + $this->validador->validar($_POST, $s->id));
        } catch (DatosInvalidos $e) {
            http_response_code(422);
            return $this->formulario('/socios/' . $s->id, $_POST, $e->errores(), false, $s->nombre);
        }
        $this->redirigir('/socios?actualizado=' . $s->id);
    }

    public function eliminar(string $id): never
    {
        $s = $this->buscarSocio($id);
        $this->socios->eliminar($s->id);     // Conflicto (409) si todavía tiene productos
        $this->redirigir('/socios?eliminado=' . $s->id);
    }

    private function formulario(string $accion, array $datos, array $errores, bool $nuevo, string $nombreActual = ''): string
    {
        $campos = ['nombre', 'comunidad', 'telefono'];
        return $this->vista->pagina('formulario_socio', [
            'accion' => $accion, 'nuevo' => $nuevo, 'errores' => $errores, 'actual' => $nombreActual,
            'datos' => array_map(fn(mixed $v): string => is_scalar($v) ? (string) $v : '',
                array_intersect_key($datos, array_flip($campos)) + array_fill_keys($campos, '')),
        ], $nuevo ? 'Registrar socio' : 'Editar socio');
    }

    /** El id llega por la URL como texto: solo dígitos, y debe existir. */
    private function buscarSocio(string $id): Socio
    {
        if (!ctype_digit($id) || strlen($id) > 9) {
            throw new NoEncontrado('No existe ese socio.');
        }
        return $this->socios->porId((int) $id) ?? throw new NoEncontrado('No existe ese socio.');
    }

    private function mensaje(): string
    {
        foreach (['creado' => 'registrado', 'actualizado' => 'actualizado', 'eliminado' => 'eliminado'] as $clave => $verbo) {
            if (is_string($_GET[$clave] ?? null) && ctype_digit($_GET[$clave])) {
                return "Socio {$_GET[$clave]} $verbo.";
            }
        }
        return '';
    }

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
