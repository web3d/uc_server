<?php

require_once __DIR__ . '/Phine/PSR4' . '/Loader.php';

$loader = new Phine\PSR4\Loader();

$loader
    ->map('uc\\server', __DIR__ . '/uc/server');