<?php
// Autoload files using composer
require_once __DIR__ . '/../vendor/autoload.php';

use Steampixel\Route;

Route::add('/', function() {
    return "Hallo";
});

Route::run('/');
?>