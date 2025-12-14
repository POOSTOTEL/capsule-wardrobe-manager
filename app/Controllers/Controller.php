<?php
// app/Controllers/Controller.php

namespace App\Controllers;

use App\Core\Config;

abstract class Controller
{
    // Рендеринг представления с макетом
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Содержимое представления
        $content = $this->renderView($view, $data);

        // Данные для макета
        $layoutData = array_merge($data, [
            'content' => $content,
            'title' => $data['title'] ?? 'Капсульный Гардероб',
            'styles' => $data['styles'] ?? ['/assets/css/app.css']
        ]);

        // Рендеринг макета
        $this->renderLayout($layout, $layoutData);
    }

    // Рендеринг представления без макета
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

    // Рендеринг макета
    protected function renderLayout(string $layout, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $layoutPath = VIEWS_PATH . '/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout file not found: {$layoutPath}");
        }

        require $layoutPath;
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
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        
        // Рекурсивно удаляем бинарные данные перед сериализацией
        $data = $this->removeBinaryData($data);
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if ($json === false) {
            // Если не удалось закодировать JSON, отправляем ошибку
            $error = json_last_error_msg();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка сериализации JSON: ' . $error,
                'json_error' => json_last_error()
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        echo $json;
        exit();
    }
    
    // Рекурсивно удаляет бинарные данные из массива
    private function removeBinaryData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'image_data') {
                    unset($data[$key]);
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = $this->removeBinaryData($value);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'image_data') {
                    unset($data->$key);
                } elseif (is_array($value) || is_object($value)) {
                    $data->$key = $this->removeBinaryData($value);
                }
            }
        }
        return $data;
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
        // Получаем данные из GET и POST
        $data = array_merge($_GET, $_POST);

        // Обработка _method для эмуляции PUT/PATCH/DELETE через POST
        if (isset($data['_method'])) {
            $_SERVER['REQUEST_METHOD'] = strtoupper($data['_method']);
            unset($data['_method']);
        }

        // Для PUT/PATCH/DELETE запросов читаем JSON из тела запроса
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $jsonData = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = array_merge($data, $jsonData);
                } else {
                    // Если не JSON, пытаемся распарсить как form-data
                    parse_str($rawInput, $parsedData);
                    if (is_array($parsedData)) {
                        $data = array_merge($data, $parsedData);
                    }
                }
            }
        }

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

    // Установка флеш-сообщения
    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
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