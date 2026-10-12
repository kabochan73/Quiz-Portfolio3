<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Claude API を使う(費用がかかる)ので、ログインしないと何も操作できない(requirements.md 3.1)。
// ログイン済みならホームへ、未ログインなら auth ミドルウェアがログイン画面へ移す
Route::redirect('/', '/home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/home', HomeController::class)->name('home');

    Route::resource('categories', CategoryController::class);
});
