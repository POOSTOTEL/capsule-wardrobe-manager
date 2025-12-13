<?php
// app/Core/Config.php

namespace App\Core;

class Config
{
    private static array $config = [];

    // Загрузка конфигурации
    public static function load(string $name): array
    {
        $configFile = __DIR__ . '/../../config/' . $name . '.php';

        if (!file_exists($configFile)) {
            throw new \RuntimeException("Config file not found: {$configFile}");
        }

        $config = require $configFile;
        self::$config[$name] = $config;

        return $config;
    }

    // Получение значения конфигурации
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $configName = array_shift($keys);

        if (!isset(self::$config[$configName])) {
            self::load($configName);
        }

        $value = self::$config[$configName] ?? null;

        foreach ($keys as $subKey) {
            if (!is_array($value) || !array_key_exists($subKey, $value)) {
                return $default;
            }
            $value = $value[$subKey];
        }

        return $value ?? $default;
    }

    // Установка значения
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $configName = array_shift($keys);

        if (!isset(self::$config[$configName])) {
            self::load($configName);
        }

        $config = &self::$config[$configName];

        foreach ($keys as $subKey) {
            if (!isset($config[$subKey]) || !is_array($config[$subKey])) {
                $config[$subKey] = [];
            }
            $config = &$config[$subKey];
        }

        $config = $value;
    }
}