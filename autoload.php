<?php
declare(strict_types=1);

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
    return;
}

require_once __DIR__ . '/src/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativePath = str_replace('\\', '/', substr($class, strlen($prefix)));
    $filePath = __DIR__ . '/src/' . $relativePath . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});