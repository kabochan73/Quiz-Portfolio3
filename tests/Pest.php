<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature テストは Laravel の TestCase で動かし、テストごとに DB を作り直す。
| Unit テストは DB や API に依存しないロジックだけを対象にするので(CLAUDE.md)、素の PHPUnit のまま。
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
