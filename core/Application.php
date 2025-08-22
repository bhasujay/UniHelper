<?php

namespace app\core;



class Application
{
    public static string $ROOT_DIR;
    public Router $router;
    public Request $request;

    public function __construct($root)
    {
        self::$ROOT_DIR = $root . '/..';
        $this->request = new Request();
        $this->router = new Router($this->request);
    }


    public function run()
    {
        echo $this->router->resolve();
    }


}