<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * ホーム = ログイン後の着地点(requirements.md 3.1.1)。
 * アプリのコンセプトと使い方を、固定の文章で表示する(screens.md 2.12)。
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home');
    }
}
