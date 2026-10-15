<?php
// Verificador.php — Ayudante mínimo para verificar.php (el original "comun/Verificador.php" no venía con el laboratorio).
// Ofrece: revisar(), servidor(), pedir() y fin(). Solo ejecuta y reporta; no altera resultados.
declare(strict_types=1);

final class Verificador
{
    private int $total = 0;
    private int $aprobadas = 0;
    /** @var resource|null */
    private $proceso = null;
    private string $base = '';

    public function revisar(string $descripcion, callable $prueba): void
    {
        $this->total++;
        try {
            $ok = $prueba() === true;
            $detalle = '';
        } catch (Throwable $e) {
            $ok = false;
            $detalle = ' [' . get_class($e) . ': ' . $e->getMessage() . ']';
        }
        if ($ok) {
            $this->aprobadas++;
        }
        echo ($ok ? 'OK    ' : 'FALLO ') . $descripcion . $detalle . PHP_EOL;
    }

    /** Arranca el servidor embebido de PHP sobre $carpeta (puerto libre, solo dentro del contenedor). */
    public function servidor(string $carpeta): void
    {
        $puerto = 0;
        for ($p = 8765; $p < 8800; $p++) {
            $s = @stream_socket_server("tcp://127.0.0.1:$p");
            if ($s !== false) {
                fclose($s);
                $puerto = $p;
                break;
            }
        }
        if ($puerto === 0) {
            throw new RuntimeException('No hay puerto libre para el servidor de pruebas.');
        }
        $this->base = "http://127.0.0.1:$puerto";
        $cmd = [PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', $carpeta];
        $this->proceso = proc_open($cmd, [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50; $i++) {
            $c = @fsockopen('127.0.0.1', $puerto, $en, $es, 0.2);
            if ($c) {
                fclose($c);
                return;
            }
            usleep(100_000);
        }
        throw new RuntimeException('El servidor de pruebas no arrancó.');
    }

    /** @return array{estado:int, cuerpo:string} */
    public function pedir(string $ruta): array
    {
        $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 10]]);
        $cuerpo = @file_get_contents($this->base . $ruta, false, $ctx);
        $estado = 0;
        foreach ($http_response_header ?? [] as $linea) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linea, $m)) {
                $estado = (int) $m[1];
            }
        }
        return ['estado' => $estado, 'cuerpo' => $cuerpo === false ? '' : $cuerpo];
    }

    public function fin(): int
    {
        if (is_resource($this->proceso)) {
            proc_terminate($this->proceso);
            proc_close($this->proceso);
        }
        echo PHP_EOL . "{$this->aprobadas} de {$this->total} comprobaciones" . PHP_EOL;
        return $this->aprobadas === $this->total ? 0 : 1;
    }
}
