<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\Grading\ClaudeGradingService;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradeResultParser;
use App\Services\Grading\GradingPrompt;
use App\Services\Grading\GradingService;
use App\Support\CostCalculator;
use App\View\Composers\SidebarComposer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\View;
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
            'claude' => $this->makeClaudeGradingService($app),
            // 設定の書き間違いに気づけるよう、知らない値はエラーにする
            default => throw new InvalidArgumentException('GRADING_DRIVER の値が正しくありません: '.$app['config']->get('services.grading.driver')),
        });

        // 推定コストの計算に使う単価は config/quiz.php から渡す(architecture.md 5章)
        $this->app->bind(CostCalculator::class, fn (Application $app) => new CostCalculator(
            $app['config']->get('quiz.pricing', []),
            $app['config']->get('quiz.web_search_per_request'),
        ));
    }

    private function makeClaudeGradingService(Application $app): ClaudeGradingService
    {
        $apiKey = $app['config']->get('services.anthropic.api_key');

        // キーがないまま動かすと、採点のたびに失敗してリトライを繰り返すので、ここで止める
        if (blank($apiKey)) {
            throw new InvalidArgumentException('GRADING_DRIVER=claude のときは ANTHROPIC_API_KEY を設定してください。');
        }

        return new ClaudeGradingService(
            client: new Client(apiKey: $apiKey),
            prompt: new GradingPrompt,
            parser: new GradeResultParser,
            model: $app['config']->get('services.anthropic.model'),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // サイドバーの部品(フェーズ5で作る)が使われるときに、カテゴリの一覧を渡す
        View::composer('components.layout.sidebar', SidebarComposer::class);
    }
}
