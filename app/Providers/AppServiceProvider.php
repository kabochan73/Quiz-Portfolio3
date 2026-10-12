<?php

namespace App\Providers;

use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradingService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 採点サービスは .env の GRADING_DRIVER で選ぶ(config/services.php)。
        // 使う側は GradingService を受け取るだけで、フェイクか本物かを知らなくてよい
        $this->app->bind(GradingService::class, fn (Application $app) => match ($app['config']->get('services.grading.driver')) {
            'fake' => $app->make(FakeGradingService::class),
            // 設定の書き間違いに気づけるよう、知らない値はエラーにする
            default => throw new InvalidArgumentException('GRADING_DRIVER の値が正しくありません: '.$app['config']->get('services.grading.driver')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
