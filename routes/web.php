<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SectionController;
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
    // 作成は /categories/{category}/sections、それ以外は /sections/{section}(requirements.md 6章)。
    // セクション一覧はカテゴリ詳細にあるので、index は作らない
    Route::resource('categories.sections', SectionController::class)->shallow()->except('index');
    // 作成は /sections/{section}/questions、それ以外は /questions/{question}(requirements.md 6章)
    Route::resource('sections.questions', QuestionController::class)->shallow()->except('index');

    Route::get('/sections/{section}/answers/create', [AnswerController::class, 'create'])->name('sections.answers.create');
    Route::post('/sections/{section}/answers', [AnswerController::class, 'store'])->name('sections.answers.store');

    Route::get('/sections/{section}/history', [HistoryController::class, 'index'])->name('sections.history.index');
    // 挑戦が URL のセクションのものでなければ 404(architecture.md 6章)
    Route::get('/sections/{section}/history/{attempt}', [HistoryController::class, 'show'])
        ->scopeBindings()
        ->name('sections.history.show');

    Route::get('/attempts/{attempt}/status', [AttemptController::class, 'status'])->name('attempts.status');
    Route::post('/attempts/{attempt}/regrade', [AttemptController::class, 'regrade'])->name('attempts.regrade');
});
