<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicPath = __DIR__.'/../public';
$requestedFile = $publicPath.$uri;

if ($uri !== '/' && is_file($requestedFile)) {
    return false;
}

require_once $publicPath.'/index.php';
