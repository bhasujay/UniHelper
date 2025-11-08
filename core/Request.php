<?php

namespace app\core;

class Request
{
    private ?array $reqBody = null; 

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
            
            // Handle uploaded files
            foreach ($_FILES as $key => $file) {
                $body[$key] = $file;
            }
        }
        // This is not use but we keep it here for future reference
        // Handle PUT and DELETE requests
        if ($this->getMethod() === 'PUT' || $this->getMethod() === 'DELETE') {
            // Parse the raw input stream
            $rawInput = file_get_contents('php://input');
            
            // Check if it's form-encoded data
            if (strpos($rawInput, '=') !== false) {
                parse_str($rawInput, $body);
                // Sanitize the parsed data
                foreach ($body as $key => $value) {
                    $body[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            } else {
                // Handle JSON data
                $jsonData = json_decode($rawInput, true);
                if ($jsonData !== null) {
                    $body = $jsonData;
                }
            }
        }
        
        return $body;
    }

    public function getQueryParams()
    {
        if ($this->getMethod() === 'GET') {
            $query = [];
            foreach ($_GET as $key => $value) {
                $query[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
            return $query;
        }
        return [];
    }

    public function get($key)
    {
        if ($this->reqBody === null) {
            $this->reqBody = $this->getBody();
        }
        return $this->reqBody[$key] ?? null;
    }
}
