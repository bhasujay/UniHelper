<?php

namespace app\core;

class Request
{

    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        $parts = explode('/', trim($path, '/'));
        array_shift($parts); // Remove the first part
        $path = '/' . implode('/', $parts);
        
        $position = strpos($path, '?');
        return ($position === false) ? $path : substr($path, 0, $position);
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getBody()
    {
        $body = [];
        
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        
        return $body;
    }

    public function get($key)
    {
        return $this->getBody()[$key] ?? null;
    }
}
