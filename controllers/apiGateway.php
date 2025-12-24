<?php

namespace app\controllers;

use app\core\Request;

class ApiGateway
{
    private $paths = [
        'qaController' => '/qaController.php',
        'otpController' => '/otpController.php',
    ];
    
    public function handleRequest(Request $request)
    {
        $controller = $request->get('controller');
        $action = $request->get('action');

        if (array_key_exists($controller, $this->paths)) {
            require_once $this->paths[$controller];
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