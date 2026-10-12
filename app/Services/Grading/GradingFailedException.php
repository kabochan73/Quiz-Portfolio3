<?php

namespace App\Services\Grading;

use RuntimeException;

/**
 * 採点の結果を受け取れなかったとき(submit_grades が呼ばれない、拒否された、時間切れなど)に投げる。
 * 採点 Job はこの例外でリトライする(architecture.md 4.3 / 4.4)。
 */
final class GradingFailedException extends RuntimeException {}
