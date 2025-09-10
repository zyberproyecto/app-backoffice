<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('hello', function () {
    $this->info('Backoffice listo 👍');
})->purpose('Mensaje de prueba del backoffice');