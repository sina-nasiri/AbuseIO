<?php

namespace Singleton;

/**
 * PHP 8.4 compatible Singleton trait
 * Overrides jover/singleton with public __wakeup() method
 */
trait Singleton
{
    /**
     * Singleton instance
     */
    protected static $instance = null;

    /**
     * Singleton clone method - protected to prevent cloning
     */
    protected function __clone() {}

    /**
     * Singleton wakeup (unserialize) method
     * Must be public in PHP 8.4+
     */
    public function __wakeup() {}

    /**
     * Return an instance of the called class
     *
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
