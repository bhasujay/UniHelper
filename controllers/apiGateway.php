<?php

namespace app\controllers;

use app\core\Request;

class ApiGateway
{    
    public function handleRequest(Request $request)
    {
        $controller = $request->get('controller');
        $action = $request->get('action');

        // Basic validation for controller and action parameters
        if (empty($controller) || empty($action)) {
            throw new \Exception("Controller and action parameters are required.");
        }

        // Protect API actions behind login, except selected public auth endpoints.
        if (!$this->isPublicRoute($controller, $action) && !$request->session('user_id')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Not authenticated.'
            ]);
            return null;
        }

        if (file_exists(dirname(__DIR__,1) . '/controllers/' . $controller . '.php')) {
            require_once dirname(__DIR__,1) . '/controllers/' . $controller . '.php';
            $controllerClass = 'app\\controllers\\' . ucfirst($controller);
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $action)) {
                    return $controllerInstance->{$action}($request);
                } else {
                    throw new \Exception("Action '$action' not found in controller '$controller'.");
                }
            } else {
                throw new \Exception("Controller class '$controllerClass' not found.");
            }
        } else {
            throw new \Exception("Controller '$controller' not recognized.");
        }
    }

    private function isPublicRoute(string $controller, string $action): bool
    {
        $publicRoutes = [
            'authcontroller' => ['checkexistsaction'],
            'otpcontroller' => ['generateotpaction', 'validateotpaction'],
        ];

        $controllerKey = strtolower($controller);
        $actionKey = strtolower($action);

        return isset($publicRoutes[$controllerKey]) && in_array($actionKey, $publicRoutes[$controllerKey], true);
    }

}