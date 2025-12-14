<?php


namespace App\Models;

class Category extends BaseModel
{
    protected $table = 'categories';
    protected $fillable = ['name', 'description'];

    
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}