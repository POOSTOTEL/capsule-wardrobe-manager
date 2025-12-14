<?php


namespace App\Controllers;

use App\Core\Config;

abstract class Controller
{
    
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        
        $content = $this->renderView($view, $data);

        
        $defaultStyles = ['/assets/css/app.css', '/assets/css/responsive.css'];
        $additionalStyles = $data['styles'] ?? [];
        
        
        $allStyles = array_merge($defaultStyles, $additionalStyles);
        $allStyles = array_unique($allStyles);
        
        $layoutData = array_merge($data, [
            'content' => $content,
            'title' => $data['title'] ?? 'Капсульный Гардероб',
            'styles' => $allStyles
        ]);

        
        $this->renderLayout($layout, $layoutData);
    }

    
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

    
    protected function renderLayout(string $layout, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $layoutPath = VIEWS_PATH . '/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout file not found: {$layoutPath}");
        }

        require $layoutPath;
    }

    
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit();
    }

    
    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        
        
        $data = $this->removeBinaryData($data);
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if ($json === false) {
            
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

    
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    
    protected function error(string $message = 'Error', int $statusCode = 400, $errors = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    
    protected function input(string $key = null, $default = null)
    {
        
        $data = array_merge($_GET, $_POST);

        
        if (isset($data['_method'])) {
            $_SERVER['REQUEST_METHOD'] = strtoupper($data['_method']);
            unset($data['_method']);
        }

        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $jsonData = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = array_merge($data, $jsonData);
                } else {
                    
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

    
    protected function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    
    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    
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