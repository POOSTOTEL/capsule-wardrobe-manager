<?php


namespace App\Models;

class Season extends BaseModel
{
    protected $table = 'seasons';
    protected $fillable = ['name'];

    
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    
    public function getMultiSeasons(): array
    {
        $seasons = $this->getAllSorted();
        $multiSeasons = [];

        
        $multiSeasons[] = ['id' => 'all', 'name' => 'Всесезонный'];

        
        $seasonNames = array_column($seasons, 'name');
        $seasonIds = array_column($seasons, 'id');

        
        $combinations = [
            ['id' => 'summer_autumn', 'name' => 'Лето-Осень'],
            ['id' => 'spring_autumn', 'name' => 'Весна-Осень'],
            ['id' => 'winter_spring', 'name' => 'Зима-Весна'],
        ];

        return array_merge($seasons, $multiSeasons, $combinations);
    }

    
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}