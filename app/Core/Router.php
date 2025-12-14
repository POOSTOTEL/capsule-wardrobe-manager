<?php


namespace App\Core;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $errorHandlers = [];
    private $currentRoute = null;

    
    public function get(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    
    public function post(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    
    public function put(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    
    public function delete(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    
    public function patch(string $path, $handler, string $name = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    
    public function any(string $path, $handler, string $name = null): self
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler, $name);
    }

    
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

    
    private function compilePattern(string $path): string
    {
        
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', '(?P<$1>[^/]+)', $path);

        
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\?\}/', '(?P<$1>[^/]*)?', $pattern);

        return '#^' . $pattern . '$#';
    }

    
    public function notFound($handler): void
    {
        $this->errorHandlers[404] = $handler;
    }

    
    public function error($handler): void
    {
        $this->errorHandlers[500] = $handler;
    }

    
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route named '{$name}' not found");
        }

        $route = $this->namedRoutes[$name];
        $path = $route['path'];

        
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
            $path = str_replace('{' . $key . '?}', $value, $path);
        }

        
        $path = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_-]*\?\}/', '', $path);

        return $path;
    }

    
    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }

    
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getCurrentUri();

        
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $this->currentRoute = $route;

                    
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    $this->executeHandler($route['handler'], $params);
                    return;
                }
            }
        }

        
        $this->handleError(404);
    }

    
    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rawurldecode($uri);

        
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
        }

        return $uri === '' ? '/' : $uri;
    }

    
    private function executeHandler($handler, array $params): void
    {
        try {
            if (is_string($handler) && strpos($handler, '@') !== false) {
                
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
                
                call_user_func_array($handler, $params);
                return;
            } elseif (is_array($handler) && count($handler) === 2) {
                
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

    
    private function handleError(int $code, \Throwable $exception = null): void
    {
        http_response_code($code);

        if (isset($this->errorHandlers[$code])) {
            $this->executeHandler($this->errorHandlers[$code], [$exception]);
        } else {
            
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