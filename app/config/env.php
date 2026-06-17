<?php
if (!function_exists('env')) {
    function env($clave, $defecto = null) {
        static $cache = null;

        if ($cache == null) {
            $cache = [];
            $ruta = __DIR__ . '/../../.env';

            if (file_exists($ruta)) {
                $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if (strpos($linea, '#') === 0) continue;
                    if (strpos($linea, '=') === false) continue;

                    $parts = explode('=', $linea, 2);
                    $nombre = trim($parts[0]);
                    $valor = trim($parts[1]);

                    if (strlen($valor) >= 2) {
                        $first = $valor[0];
                        $last = substr($valor, -1);
                        if (($first == '"' && $last == '"') || ($first == "'" && $last == "'")) {
                            $valor = substr($valor, 1, -1);
                        }
                    }

                    $cache[$nombre] = $valor;
                }
            }
        }

        return $cache[$clave] ?? $defecto;
    }
}
?>