<?php

/**
 * Test bootstrap: load Composer autoload and provide minimal helpers
 * when tests run outside a full Laravel application.
 */

if (!function_exists('config')) {
    function config(?string $key = null, $default = null)
    {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = ['swift-auth' => []];
            $path = __DIR__ . '/../src/config/swift-auth.php';
            if (file_exists($path)) {
                $cfg['swift-auth'] = require $path;
            }
        }

        if ($key === null) {
            return $cfg;
        }

        $parts = explode('.', $key);
        $val = $cfg;
        foreach ($parts as $p) {
            if (is_array($val) && array_key_exists($p, $val)) {
                $val = $val[$p];
            } else {
                return $default;
            }
        }

        return $val;
    }
}

if (!function_exists('logger')) {
    function logger()
    {
        return new class {
            public function warning(...$args) {}
            public function error(...$args) {}
            public function info(...$args) {}
            public function debug(...$args) {}
        };
    }
}

if (!function_exists('now')) {
    function now()
    {
        return new \DateTimeImmutable('now');
    }
}

// Load Composer autoload after defining helpers so vendor helpers don't override them.
require __DIR__ . '/../vendor/autoload.php';
