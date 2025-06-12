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

header("Content-Type: $contentType");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

$ctx = stream_context_create([
    'http' => [
        'follow_location' => true,
        'timeout' => 30
    ]
]);

$stream = @fopen($url, 'rb', false, $ctx);
if (!$stream) {
    http_response_code(502);
    echo "❌ No se pudo acceder al video.";
    exit;
}

while (!feof($stream)) {
    echo fread($stream, 8192);
    flush();
}

fclose($stream);
exit;
