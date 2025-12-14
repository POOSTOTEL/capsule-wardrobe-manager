<?php
// app/Services/TaxonomyService.php

namespace App\Services;

use App\Models\Category;
use App\Models\Color;
use App\Models\Season;

class TaxonomyService
{
    protected $categoryModel;
    protected $colorModel;
    protected $seasonModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->colorModel = new Color();
        $this->seasonModel = new Season();
    }

    // Получить все справочники для форм
    public function getAllForSelect(): array
    {
        return [
            'categories' => $this->categoryModel->getForSelect(),
            'colors' => $this->colorModel->getForSelect(),
            'seasons' => $this->seasonModel->getForSelect(),
        ];
    }

    // Получить все справочники с деталями
    public function getAllWithDetails(): array
    {
        return [
            'categories' => $this->categoryModel->getAllSorted(),
            'colors' => $this->colorModel->getWithHexCodes(),
            'seasons' => $this->seasonModel->getAllSorted(),
        ];
    }

    // Получить категории для формы выбора
    public function getCategoriesForForm(): array
    {
        $categories = $this->categoryModel->getAllSorted();
        $options = ['' => '-- Выберите категорию --'];

        foreach ($categories as $category) {
            $options[$category['id']] = $category['name'];
        }

        return $options;
    }

    // Получить цвета для формы выбора
    public function getColorsForForm(): array
    {
        $colors = $this->colorModel->getAllSorted();
        $options = ['' => '-- Выберите цвет --'];

        foreach ($colors as $color) {
            $options[$color['id']] = $color['name'];
        }

        return $options;
    }

    // Получить сезоны для формы выбора
    public function getSeasonsForForm(): array
    {
        $seasons = $this->seasonModel->getAllSorted();
        $options = ['' => '-- Выберите сезон --'];

        foreach ($seasons as $season) {
            $options[$season['id']] = $season['name'];
        }

        return $options;
    }

    // Получить все данные для формы добавления вещи
    public function getFormData(): array
    {
        return [
            'categoryOptions' => $this->getCategoriesForForm(),
            'colorOptions' => $this->getColorsForForm(),
            'seasonOptions' => $this->getSeasonsForForm(),
            'colorsWithHex' => $this->colorModel->getWithHexCodes(),
        ];
    }

    // Валидация ID справочников
    public function validateTaxonomyIds(array $data): array
    {
        $errors = [];

        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $category = $this->categoryModel->find($data['category_id']);
            if (!$category) {
                $errors['category_id'] = 'Указана несуществующая категория';
            }
        }

        if (isset($data['color_id']) && !empty($data['color_id'])) {
            $color = $this->colorModel->find($data['color_id']);
            if (!$color) {
                $errors['color_id'] = 'Указан несуществующий цвет';
            }
        }

        if (isset($data['season_id']) && !empty($data['season_id'])) {
            $season = $this->seasonModel->find($data['season_id']);
            if (!$season) {
                $errors['season_id'] = 'Указан несуществующий сезон';
            }
        }

        return $errors;
    }

    // Получить название по ID
    public function getNameById(string $type, int $id): ?string
    {
        switch ($type) {
            case 'category':
                $item = $this->categoryModel->find($id);
                break;
            case 'color':
                $item = $this->colorModel->find($id);
                break;
            case 'season':
                $item = $this->seasonModel->find($id);
                break;
            default:
                return null;
        }

        return $item ? $item['name'] : null;
    }

    // Получить все справочники для API
    public function getForApi(): array
    {
        return [
            'success' => true,
            'data' => $this->getAllWithDetails(),
            'meta' => [
                'total_categories' => $this->categoryModel->count(),
                'total_colors' => $this->colorModel->count(),
                'total_seasons' => $this->seasonModel->count(),
            ]
        ];
    }
}