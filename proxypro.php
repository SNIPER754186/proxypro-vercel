<?php
ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_GET['url'])) {
    http_response_code(400);
    echo "❌ Parámetro 'url' requerido.";
    exit;
}

$url = $_GET['url'];

if (!preg_match('#^http://vod\.tuxchannel\.mx/#i', $url)) {
    http_response_code(403);
    echo "❌ Dominio no permitido o URL inválida.";
    exit;
}

$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
$mimeTypes = [
    'mp4'  => 'video/mp4',
    'mkv'  => 'video/x-matroska',
    'webm' => 'video/webm',
    'm3u8' => 'application/vnd.apple.mpegurl',
    'ts'   => 'video/MP2T'
];
$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

$headers = getallheaders(); // Obtener cabeceras de la petición
$range = $headers['Range'] ?? $headers['range'] ?? null;

// Crear contexto para fopen con cabecera Range si existe
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => '',
        'follow_location' => true,
        'timeout' => 30
    ]
];

if ($range) {
    $opts['http']['header'] = "Range: $range";
}

$ctx = stream_context_create($opts);

// Abrir conexión al recurso remoto
$stream = @fopen($url, 'rb', false, $ctx);

if (!$stream) {
    http_response_code(502);
    echo "❌ No se pudo acceder al video.";
    exit;
}

// Extraer metadatos del recurso remoto para calcular rango y tamaño
// Usamos get_headers para obtener Content-Length y Accept-Ranges
$remoteHeaders = get_headers($url, 1);

$contentLength = $remoteHeaders['Content-Length'] ?? null;
$acceptRanges = $remoteHeaders['Accept-Ranges'] ?? null;

// Si el servidor remoto soporta rangos y el cliente los pide:
if ($range && $acceptRanges == 'bytes') {
    // Parsear rango (por ejemplo: bytes=0-1023)
    if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        $start = $matches[1] === '' ? 0 : intval($matches[1]);
        $end = $matches[2] === '' ? ($contentLength ? $contentLength - 1 : null) : intval($matches[2]);
        $length = ($end !== null && $start !== null) ? ($end - $start + 1) : null;

        http_response_code(206); // Partial Content
        header("Content-Type: $contentType");
        header("Accept-Ranges: bytes");
        if ($contentLength) {
            header("Content-Length: $length");
        }
        header("Content-Range: bytes $start-$end/$contentLength");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Range");
        header("Access-Control-Allow-Methods: GET, HEAD, OPTIONS");
    }
} else {
    // Respuesta normal
    header("Content-Type: $contentType");
    if ($contentLength) {
        header("Content-Length: $contentLength");
    }
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: *");
}

// Leer y enviar contenido en chunks
while (!feof($stream)) {
    echo fread($stream, 8192);
    flush();
}

fclose($stream);
exit;
?>
