<?php


namespace App\Models;

class Color extends BaseModel
{
    protected $table = 'colors';
    protected $fillable = ['name', 'hex_code'];

    
    public function getAllSorted(): array
    {
        return $this->allOrdered('name', 'ASC');
    }

    
    public function findByName(string $name): ?array
    {
        return $this->firstWhere('name', $name);
    }

    
    

    
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

    
    private function getContrastColor($hexColor): string
    {
        
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }

    
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        return parent::getForSelect($valueField, $keyField);
    }
}