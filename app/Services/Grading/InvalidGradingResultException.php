<?php

namespace App\Services\Grading;

use RuntimeException;

/**
 * AI が返した採点結果が不正なとき(件数が合わない、項目が空など)に投げる。
 * 採点 Job はこの例外でリトライする(architecture.md 4.4)。
 */
final class InvalidGradingResultException extends RuntimeException {}
