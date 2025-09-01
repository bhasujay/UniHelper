<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;

class AuthController
{
    // Show login form
    public function login(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            // process login
            $body = $request->getBody();

            // Example: validate user with DB
            $username = $body['username'] ?? '';
            $password = $body['password'] ?? '';

            // Here you'd query DB and check password hash
            if ($username === 'test' && $password === '123') {
                $_SESSION['user'] = $username; // set session
                header('Location: /dashboard');
                exit;
            }

            return "Invalid login!";
        }

        return $this->render('login.html'); // show form
    }

    // Show register form
    public function register(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $body = $request->getBody();

            // Insert into DB (hash password, etc.)
            // ...
            return "User registered!";
        }

        return $this->render('register.html');
    }

    protected function render($view)
    {
        require Application::$ROOT_DIR . "/views/$view";
    }
}
