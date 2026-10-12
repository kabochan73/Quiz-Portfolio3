<?php

namespace App\Support;

/**
 * 採点1回(Attempt)にかかった Claude API の推定コスト(ドル)を計算する(architecture.md 5章)。
 * 単価は config/quiz.php の pricing から渡す。DB や API に依存しないので、Unit テストで確かめる。
 */
final readonly class CostCalculator
{
    /**
     * @param  array<string, array{input_per_mtok: mixed, output_per_mtok: mixed}>  $pricing  モデル名 => 100万トークンあたりの単価
     * @param  mixed  $webSearchPerRequest  Web検索1回あたりの単価
     */
    public function __construct(
        private array $pricing,
        private mixed $webSearchPerRequest,
    ) {}

    /**
     * 推定コスト = 入力トークン × 入力単価 + 出力トークン × 出力単価 + 検索回数 × 検索単価
     *
     * @return float|null 必要な単価が設定されていなければ null(画面ではコストを出さず、トークン数だけを出す)
     */
    public function estimate(string $model, int $inputTokens, int $outputTokens, int $webSearchRequests): ?float
    {
        $inputPrice = $this->price($this->pricing[$model]['input_per_mtok'] ?? null);
        $outputPrice = $this->price($this->pricing[$model]['output_per_mtok'] ?? null);

        if ($inputPrice === null || $outputPrice === null) {
            return null;
        }

        $searchPrice = $this->price($this->webSearchPerRequest);

        // 検索していなければ、検索の単価がなくても計算できる
        if ($webSearchRequests > 0 && $searchPrice === null) {
            return null;
        }

        return $inputTokens / 1_000_000 * $inputPrice
            + $outputTokens / 1_000_000 * $outputPrice
            + $webSearchRequests * ($searchPrice ?? 0.0);
    }

    /**
     * .env の値は文字列で渡ってくるので、数値に直す。空や数値でないものは「未設定」とみなす。
     */
    private function price(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
