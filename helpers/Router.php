<?php
// helpers/Router.php
// Router con soporte para placeholders y validación de seguridad

class Router
{
    private array $routes = [];
    private string $notFound = '';

    public function add(string $route, string $handler): void
    {
        $route = str_replace(':num', '@@NUM@@', $route);
        $route = str_replace(':any', '@@ANY@@', $route);
        $route = preg_quote($route, '/');
        $route = str_replace('@@NUM@@', '([0-9]+)', $route);
        $route = str_replace('@@ANY@@', '([a-zA-Z0-9\-_]+)', $route);
        $route = '/^' . $route . '$/';

        $this->routes[] = [
            'pattern' => $route,
            'handler' => $handler,
        ];
    }

    public function setNotFound(string $route): void
    {
        $this->notFound = $route;
    }

    public function dispatch(string $url): void
    {
        $url = trim($url, '/');
        if ($url === '') {
            $url = 'auth/login';
        }

        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $url, $matches)) {
                array_shift($matches);
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        $this->show404();
    }

    private function executeHandler(string $handler, array $params = []): void
    {
        $parts = explode('@', $handler);
        if (count($parts) !== 2) {
            $this->show404();
            return;
        }

        [$controllerName, $methodName] = $parts;

        if (!preg_match('/^[A-Za-z0-9_]+$/', $controllerName) ||
            !preg_match('/^[A-Za-z0-9_]+$/', $methodName)) {
            $this->show404();
            return;
        }

        $controllerFile = __DIR__ . '/../controller/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            $this->show404();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->show404();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            $this->show404();
            return;
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    private function show404(): void
    {
        http_response_code(404);

        $controllerFile = __DIR__ . '/../controller/ErrorController.php';
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            if (class_exists('ErrorController')) {
                $errorCtrl = new ErrorController();
                if (method_exists($errorCtrl, 'error404')) {
                    $errorCtrl->error404();
                    return;
                }
            }
        }

        echo '<h1>404 - Página no encontrada</h1>';
    }
}