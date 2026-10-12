<?php

namespace App\Services;

use RuntimeException;

/**
 * セクションの問題数が上限(既定 10問)に達しているのに、問題を追加しようとしたときに投げる(requirements.md 3.2)。
 * 画面では入力エラーとして「1セクションに登録できる問題は10問までです」にあたる文言を出す。
 */
final class QuestionLimitExceededException extends RuntimeException {}
