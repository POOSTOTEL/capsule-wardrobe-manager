<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Капсульный Гардероб - Главная',
            'styles' => ['/assets/css/dashboard.css'],
            'content' => $this->renderView('home/index')
        ];

        $this->renderLayout('main', $data);
    }

    // Вспомогательный метод для рендеринга представления
    protected function renderView(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        $viewPath = VIEWS_PATH . '/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}");
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    // Вспомогательный метод для рендеринга макета
    protected function renderLayout(string $layout, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $layoutPath = VIEWS_PATH . '/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout file not found: {$layoutPath}");
        }

        require $layoutPath;
    }
}