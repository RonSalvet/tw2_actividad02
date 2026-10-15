<?php
// public/index.php — FRONT CONTROLLER del laboratorio 8: la ÚNICA puerta de entrada.
//   Servidor de desarrollo:  php -S 127.0.0.1:8000 -t public public/index.php
declare(strict_types=1);

// Con php -S: si piden un archivo que existe (CSS, imágenes), que lo entregue el servidor
if (PHP_SAPI === 'cli-server') {
    $archivo = realpath(__DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    if ($archivo !== false && str_starts_with($archivo, realpath(__DIR__) . DIRECTORY_SEPARATOR) && is_file($archivo)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\BaseDatos\ProductoConsultas;
use Apicultores\Controladores\ProductoControlador;
use Apicultores\Excepciones\ErrorAplicacion;
use Apicultores\Excepciones\MetodoNoPermitido;
use Apicultores\Http\Router;
use Apicultores\Vista\Plantilla;

ini_set('display_errors', '0');                   // nunca se muestran detalles internos
const REGISTRO_ERRORES = __DIR__ . '/../datos/errores.log';

set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

$vista = new Plantilla(__DIR__ . '/../plantillas');

try {
    $c = new ProductoControlador(new ProductoConsultas(Conexion::obtener()), $vista);

    $router = new Router();
    $router->get('/', $c->listar(...));
    $router->get('/productos', $c->listar(...));
    $router->get('/productos/nuevo', $c->nuevo(...));
    $router->post('/productos', $c->crear(...));
    $router->get('/productos/{codigo}/editar', $c->editar(...));
    $router->post('/productos/{codigo}/editar', $c->actualizar(...));
    $router->post('/productos/{codigo}/eliminar', $c->eliminar(...));

    echo $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (ErrorAplicacion $e) {
    // Errores PREVISTOS (404, 405, 409): su mensaje lo escribimos nosotros, es seguro mostrarlo
    http_response_code($e->codigoHttp());
    if ($e instanceof MetodoNoPermitido) {
        header('Allow: ' . implode(', ', $e->permitidos()));
    }
    echo $vista->pagina('error', ['codigo' => $e->codigoHttp(), 'titulo' => $e->titulo(), 'mensaje' => $e->getMessage()], $e->titulo());
} catch (Throwable $e) {
    // Errores INESPERADOS (500, por ejemplo la base no responde): el detalle va al registro; al usuario, una referencia
    $referencia = bin2hex(random_bytes(4));
    error_log(sprintf("[%s] ref=%s %s: %s en %s:%d\n", date('c'), $referencia, $e::class,
        $e->getMessage(), $e->getFile(), $e->getLine()), 3, REGISTRO_ERRORES);
    http_response_code(500);
    echo $vista->pagina('error', ['codigo' => 500, 'titulo' => 'Algo salió mal',
        'mensaje' => "Tuvimos un problema inesperado. Si persiste, avisa a soporte con la referencia $referencia."], 'Error');
}
