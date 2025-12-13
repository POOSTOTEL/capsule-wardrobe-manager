<?php
// app/Core/Config.php

namespace App\Core;

class Config
{
    private static $configs = [];

    public static function load(string $name)
    {
        if (!isset(self::$configs[$name])) {
            $file = CONFIG_PATH . '/' . $name . '.php';
            if (file_exists($file)) {
                self::$configs[$name] = require $file;
            } else {
                self::$configs[$name] = [];
            }
        }

        return self::$configs[$name];
    }

    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            $config = self::load($parts[0]);
            return $config;
        }

        $configName = array_shift($parts);
        $config = self::load($configName);

        $current = $config;
        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }
}