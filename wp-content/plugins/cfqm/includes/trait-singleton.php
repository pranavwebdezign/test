<?php
namespace CFQM;
defined('ABSPATH') || exit;

trait Singleton {
    private static array $instances = [];

    public static function instance(): static {
        $key = static::class;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new static();
        }
        return self::$instances[$key];
    }

    private function __construct() {}
    private function __clone() {}
}
