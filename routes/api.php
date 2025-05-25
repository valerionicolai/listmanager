<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SilentLoginController; // Add this line

Route::post('/silent-login', [SilentLoginController::class, 'handle']);