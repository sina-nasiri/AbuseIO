<?php

/**
 * Fix jover/singleton for PHP 8.4 compatibility
 * Changes protected __wakeup() to public __wakeup()
 */

$file = __DIR__ . '/../vendor/jover/singleton/src/Singleton.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $content = str_replace(
        'protected function __wakeup()',
        'public function __wakeup()',
        $content
    );
    file_put_contents($file, $content);
    echo "Patched jover/singleton for PHP 8.4 compatibility\n";
}
