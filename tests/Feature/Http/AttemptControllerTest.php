<?php

use App\Enums\AttemptStatus;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// 画面(Blade)はまだないので、ここでは JSON の応答・再採点・URL の親子関係の確認だけを確かめる。画面の表示はフェーズ5で確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('採点状態を JSON で返す', function (AttemptStatus $status) {
    $attempt = Attempt::factory()->create(['status' => $status]);

    $this->getJson("/attempts/{$attempt->id}/status")
        ->assertOk()
        ->assertExactJson(['status' => $status->value]);
})->with(AttemptStatus::cases());

test('失敗した挑戦を再採点すると、待機中に戻して採点 Job を入れ、結果画面へ移る', function () {
    // architecture.md 4.7
    Queue::fake();
    $attempt = Attempt::factory()->failed()->create();

    $this->post("/attempts/{$attempt->id}/regrade")
        ->assertRedirect(route('sections.history.show', [$attempt->section_id, $attempt]));

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Pending)
        ->and($attempt->fresh()->error_message)->toBeNull();
    Queue::assertPushed(GradeAttempt::class, fn (GradeAttempt $job) => $job->attempt->is($attempt));
});

test('失敗していない挑戦は再採点しない', function (AttemptStatus $status) {
    // 採点中や完了済みでボタンを押し直しても、二重に採点しない
    Queue::fake();
    $attempt = Attempt::factory()->create(['status' => $status]);

    $this->post("/attempts/{$attempt->id}/regrade")
        ->assertRedirect(route('sections.history.show', [$attempt->section_id, $attempt]));

    expect($attempt->fresh()->status)->toBe($status);
    Queue::assertNothingPushed();
})->with([AttemptStatus::Pending, AttemptStatus::Grading, AttemptStatus::Completed]);

test('別のセクションの挑戦を、URL で指定されたセクションの結果として開けない', function () {
    // architecture.md 6章: URL の親子関係が正しくなければ 404
    $attempt = Attempt::factory()->create();
    $otherSection = Section::factory()->create();

    $this->get("/sections/{$otherSection->id}/history/{$attempt->id}")->assertNotFound();
});

test('存在しない挑戦・セクションは 404', function () {
    $section = Section::factory()->create();

    $this->getJson('/attempts/999999/status')->assertNotFound();
    $this->post('/attempts/999999/regrade')->assertNotFound();
    $this->get("/sections/{$section->id}/history/999999")->assertNotFound();
    $this->get('/sections/999999/history')->assertNotFound();
});

test('ログインしていなければ、状態の確認も再採点もできない', function () {
    Queue::fake();
    auth()->logout();
    $attempt = Attempt::factory()->failed()->create();

    $this->getJson("/attempts/{$attempt->id}/status")->assertUnauthorized();
    $this->post("/attempts/{$attempt->id}/regrade")->assertRedirect(route('login'));

    Queue::assertNothingPushed();
});
