<?php

$app->router->get('/','home.html');
$app->router->get('/home','home.html');
$app->router->get('/login','login.html');
$app->router->get('/register','register.html');
$app->router->get('/applicant','dashboard_app.php');
$app->router->get('/undergrad','dashboard_und.php');
$app->router->get('/profile','dashboard_pro.php');
$app->router->get('/moderator','dashboard_mod.php');
$app->router->get('/layout','layouts/layout1.html');