<?php

/*
|--------------------------------------------------------------------------
| Suppress Deprecation Notices for PHP 8.4 Compatibility
|--------------------------------------------------------------------------
|
| Laravel 7 uses implicit nullable parameters which are deprecated in PHP 8.4.
| We suppress these notices until the upgrade to Laravel 10+ is complete.
| This MUST happen before any class loading to prevent deprecation exceptions.
|
*/

// Set error reporting level to exclude deprecation notices
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Register a global error handler that suppresses deprecation notices
// This is needed because Composer's autoloader may trigger deprecations during class loading
$_deprecationHandler = set_error_handler(function ($severity, $message, $file, $line) use (&$_deprecationHandler) {
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        return true; // Suppress deprecation notices
    }
    // Call previous handler if it exists
    if ($_deprecationHandler) {
        return call_user_func($_deprecationHandler, $severity, $message, $file, $line);
    }
    return false;
});

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Include The Compiled Class File
|--------------------------------------------------------------------------
|
| To dramatically increase your application's performance, you may use a
| compiled class file which contains all of the classes commonly used
| by a request. The Artisan "optimize" is used to create this file.
|
*/

$compiledPath = __DIR__.'/cache/compiled.php';

if (file_exists($compiledPath)) {
    require $compiledPath;
}
