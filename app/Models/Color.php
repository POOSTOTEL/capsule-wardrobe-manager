<?php
// app/Models/Color.php

namespace App\Models;

class Color extends BaseModel
{
    protected $table = 'colors';
    protected $fillable = ['name', 'hex_code'];

    // Получить все цвета с сортировкой по имени
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    // Найти цвет по названию
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    // Получить цвета для выпадающего списка (используем метод базового класса)
    // Не нужно переопределять, если используется с теми же параметрами

    // Получить цвета с hex-кодами для отображения
    public function getWithHexCodes(): array
    {
        $colors = $this->getAllSorted();
        $result = [];

        foreach ($colors as $color) {
            $result[] = [
                'id' => $color['id'],
                'name' => $color['name'],
                'hex_code' => $color['hex_code'] ?? '#CCCCCC',
                'text_color' => $this->getContrastColor($color['hex_code'] ?? '#CCCCCC')
            ];
        }

        return $result;
    }

    // Определить контрастный цвет текста для фона
    private function getContrastColor($hexColor): string
    {
        // Удаляем # если есть
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Формула яркости
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }

    // Переопределяем метод, если нужна специфичная логика
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}