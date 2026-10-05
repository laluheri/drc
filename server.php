<?php

// Router for the local PHP development server, started from the project root.
$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$target = realpath($publicPath.$uri);
if ($uri !== '/' && $target && str_starts_with(str_replace('\\', '/', $target), str_replace('\\', '/', $publicPath).'/') && is_file($target)) {
    return false;
}
require_once $publicPath.'/index.php';
