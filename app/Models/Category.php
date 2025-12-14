<?php
// app/Models/Category.php

namespace App\Models;

class Category extends BaseModel
{
    protected $table = 'categories';
    protected $fillable = ['name', 'description'];

    // Получить все категории с сортировкой по имени
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    // Найти категорию по названию
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    // Переопределяем метод, если нужна специфичная логика
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}