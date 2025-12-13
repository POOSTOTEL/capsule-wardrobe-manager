<?php
// app/Core/Router.php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $errorHandlers = [];
    private $currentRoute = null;

    // Регистрация маршрута GET
    public function get(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    // Регистрация маршрута POST
    public function post(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    // Регистрация маршрута PUT
    public function put(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    // Регистрация маршрута DELETE
    public function delete(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    // Регистрация маршрута PATCH
    public function patch(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    // Регистрация любого метода
    public function any(string $path, $handler, string $name = null): self
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler, $name);
    }

    // Добавление маршрута
    private function addRoute($methods, string $path, $handler, ?string $name): self
    {
        $methods = (array)$methods;
        $pattern = $this->compilePattern($path);

        foreach ($methods as $method) {
            $this->routes[$method][] = [
                'pattern' => $pattern,
                'handler' => $handler,
                'originalPath' => $path,
                'name' => $name
            ];

            if ($name) {
                $this->namedRoutes[$name] = [
                    'method' => $method,
                    'path' => $path
                ];
            }
        }

        return $this;
    }

    // Компиляция паттерна для регулярного выражения
    private function compilePattern(string $path): string
    {
        // Заменяем параметры вида {param} на именованные группы
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', '(?P<$1>[^/]+)', $path);

        // Заменяем необязательные параметры {param?}
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\?\}/', '(?P<$1>[^/]*)?', $pattern);

        return '#^' . $pattern . '$#';
    }

    // Обработка 404 ошибки
    public function notFound($handler): void
    {
        $this->errorHandlers[404] = $handler;
    }

    // Обработка 500 ошибки
    public function error($handler): void
    {
        $this->errorHandlers[500] = $handler;
    }

    // Генерация URL по имени маршрута
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route named '{$name}' not found");
        }

        $route = $this->namedRoutes[$name];
        $path = $route['path'];

        // Заменяем параметры в пути
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
            $path = str_replace('{' . $key . '?}', $value, $path);
        }

        // Удаляем оставшиеся необязательные параметры
        $path = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_-]*\?\}/', '', $path);

        return $path;
    }

    // Получение текущего маршрута
    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }

    // Запуск роутера
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getCurrentUri();

        // Проверяем маршруты для текущего метода
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $this->currentRoute = $route;

                    // Фильтруем только именованные параметры
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    $this->executeHandler($route['handler'], $params);
                    return;
                }
            }
        }

        // Маршрут не найден - 404
        $this->handleError(404);
    }

    // Получение текущего URI
    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rawurldecode($uri);

        // Удаляем базовый путь если есть
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
        }

        return $uri === '' ? '/' : $uri;
    }

    // Выполнение обработчика
    private function executeHandler($handler, array $params): void
    {
        try {
            if (is_string($handler) && strpos($handler, '@') !== false) {
                // Формат: Controller@method
                list($controller, $method) = explode('@', $handler, 2);
                $controller = 'App\\Controllers\\' . $controller;

                if (class_exists($controller)) {
                    $controllerInstance = new $controller();

                    if (method_exists($controllerInstance, $method)) {
                        call_user_func_array([$controllerInstance, $method], $params);
                        return;
                    }
                }
            } elseif (is_callable($handler)) {
                // Callable функция или замыкание
                call_user_func_array($handler, $params);
                return;
            } elseif (is_array($handler) && count($handler) === 2) {
                // Массив [Controller::class, 'method']
                if (is_string($handler[0])) {
                    $controller = 'App\\Controllers\\' . $handler[0];
                    $handler[0] = new $controller();
                }
                call_user_func_array($handler, $params);
                return;
            }

            throw new \RuntimeException('Invalid route handler');

        } catch (\Throwable $e) {
            error_log('Router error: ' . $e->getMessage());
            $this->handleError(500, $e);
        }
    }

    // Обработка ошибок
    private function handleError(int $code, \Throwable $exception = null): void
    {
        http_response_code($code);

        if (isset($this->errorHandlers[$code])) {
            $this->executeHandler($this->errorHandlers[$code], [$exception]);
        } else {
            // Дефолтная обработка ошибок
            if ($code === 404) {
                echo '<h1>404 - Page Not Found</h1>';
                echo '<p>The requested page could not be found.</p>';
            } else {
                echo '<h1>500 - Internal Server Error</h1>';
                if (Config::get('app.debug', false)) {
                    echo '<pre>' . htmlspecialchars($exception->getMessage()) . '</pre>';
                }
            }
        }
    }
}