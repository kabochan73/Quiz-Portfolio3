<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * ホーム = ログイン後の着地点(requirements.md 3.1.1)。
 * いずれアプリのコンセプトと使い方を表示する。文章が決まるまでは、New category ボタンだけを置く(screens.md 2.12)。
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home');
    }
}
