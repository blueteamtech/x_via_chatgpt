<?php

use App\Http\Controllers\XAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'mcpUrl' => url('/mcp'),
        'callbackUrl' => url('/auth/x/callback'),
    ]);
})->name('home');

Route::get('/login', fn () => redirect()->route('auth.x'))->name('login');

Route::get('/auth/x', [XAuthController::class, 'redirect'])->name('auth.x');
Route::get('/auth/x/callback', [XAuthController::class, 'callback'])->name('auth.x.callback');
Route::post('/logout', [XAuthController::class, 'logout'])->middleware('auth')->name('logout');
