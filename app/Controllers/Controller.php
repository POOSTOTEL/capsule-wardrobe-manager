<?php
// app/Controllers/Controller.php

namespace App\Controllers;

use App\Core\Config;

abstract class Controller
{
    // Рендеринг представления
    protected function view(string $view, array $data = []): void
    {
        // Преобразуем ключи массива в переменные
        extract($data, EXTR_SKIP);

        // Путь к файлу представления
        $viewPath = Config::get('paths.views', __DIR__ . '/../../public/views/') . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}");
        }

        // Включаем файл представления
        require $viewPath;
    }

    // Рендеринг с макетом
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Содержимое представления
        ob_start();
        $this->view($view, $data);
        $content = ob_get_clean();

        // Рендеринг макета
        $this->view('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    // Перенаправление
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit();
    }

    // JSON ответ
    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    // Успешный JSON ответ
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    // Ошибка JSON ответ
    protected function error(string $message = 'Error', int $statusCode = 400, $errors = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    // Проверка AJAX запроса
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // Получение данных запроса
    protected function input(string $key = null, $default = null)
    {
        $data = array_merge($_GET, $_POST);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    // Получение загруженного файла
    protected function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    // Валидация
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rules = explode('|', $ruleString);

            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "Поле обязательно для заполнения";
                }

                if (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "Минимальная длина: {$min} символов";
                    }
                }

                if (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field][] = "Максимальная длина: {$max} символов";
                    }
                }

                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Неверный формат email";
                }
            }
        }

        return $errors;
    }
}