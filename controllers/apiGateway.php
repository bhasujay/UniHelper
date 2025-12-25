<?php

namespace app\controllers;

use app\core\Request;

class ApiGateway
{    
    public function handleRequest(Request $request)
    {
        $controller = $request->get('controller');
        $action = $request->get('action');

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

}