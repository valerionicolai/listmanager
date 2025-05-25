<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SilentLoginController;

Route::get('/', function () {
    return view('welcome');
});

