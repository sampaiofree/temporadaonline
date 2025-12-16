<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard');

Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::view('/perfil', 'perfil')->name('perfil');

Route::view('/minha_liga', 'minha_liga')->name('minha_liga');

require __DIR__.'/auth.php';
