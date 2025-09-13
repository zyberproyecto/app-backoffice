<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => [
    'pong' => true,
    'app'  => 'backoffice',
    'ts'   => now()->toIso8601String(),
]);