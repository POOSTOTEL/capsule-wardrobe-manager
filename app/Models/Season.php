<?php
// app/Models/Season.php

namespace App\Models;

class Season extends BaseModel
{
    protected $table = 'seasons';
    protected $fillable = ['name'];

    // Получить все сезоны с сортировкой
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    // Найти сезон по названию
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    // Получить мультисезонные варианты
    public function getMultiSeasons(): array
    {
        $seasons = $this->getAllSorted();
        $multiSeasons = [];

        // Добавляем комбинированные сезоны
        $multiSeasons[] = ['id' => 'all', 'name' => 'Всесезонный'];

        // Создаем комбинации сезонов
        $seasonNames = array_column($seasons, 'name');
        $seasonIds = array_column($seasons, 'id');

        // Пример: Лето+Осень, Весна+Осень и т.д.
        $combinations = [
            ['id' => 'summer_autumn', 'name' => 'Лето-Осень'],
            ['id' => 'spring_autumn', 'name' => 'Весна-Осень'],
            ['id' => 'winter_spring', 'name' => 'Зима-Весна'],
        ];

        return array_merge($seasons, $multiSeasons, $combinations);
    }

    // Переопределяем метод, если нужна специфичная логика
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}