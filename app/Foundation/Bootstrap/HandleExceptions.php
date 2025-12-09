<?php

namespace AbuseIO\Foundation\Bootstrap;

use ErrorException;
use Illuminate\Foundation\Bootstrap\HandleExceptions as BaseHandleExceptions;

/**
 * Custom HandleExceptions that suppresses deprecation notices.
 *
 * This is needed because Laravel 7 uses implicit nullable parameters which
 * are deprecated in PHP 8.4. We suppress these notices until the upgrade
 * to Laravel 10+ is complete.
 */
class HandleExceptions extends BaseHandleExceptions
{
    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        // Skip deprecation notices for PHP 8.4 compatibility
        if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED) {
            return;
        }

        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }
}
