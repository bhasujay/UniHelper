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

        echo $path;

        return ($position === false) ? $path : substr($path, 0, $position);
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}
