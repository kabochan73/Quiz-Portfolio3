<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

/**
 * ホーム = ログイン後の着地点。アプリのコンセプトと使い方を表示する(requirements.md 3.1.1)。
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            // カテゴリが0件なら、一番下のボタンを「Create your first category」にする(screens.md 2.12)
            'hasCategories' => Category::query()->exists(),
        ]);
    }
}
