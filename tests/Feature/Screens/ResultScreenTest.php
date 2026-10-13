<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;

// 採点結果 = 履歴詳細(screens.md 2.10)。採点中の確認(ポーリング)の動きは JS なので、ブラウザで確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
    [$this->q1, $this->q2] = Question::factory()->count(2)->for($this->section)->sequence(
        ['body' => 'TCPとUDPの違いは?'],
        ['body' => 'DNSとは?'],
    )->create();
});

function resultUrl(Section $section, Attempt $attempt): string
{
    return "/sections/{$section->id}/history/{$attempt->id}";
}

/**
 * 回答だけを保存した挑戦(採点前)を作る。
 */
function unscoredAttempt(Section $section, array $questions, AttemptStatus $status): Attempt
{
    $attempt = Attempt::factory()->for($section)->create(['status' => $status]);
    foreach ($questions as $position => $question) {
        $attempt->answers()->create(['question_id' => $question->id, 'position' => $position, 'body' => "回答 {$position}"]);
    }

    return $attempt;
}

test('採点中は、何問を採点しているかと、結果のカードの形を出し、状態を確かめ続ける', function (AttemptStatus $status) {
    $attempt = unscoredAttempt($this->section, [$this->q1, $this->q2], $status);

    $this->get(resultUrl($this->section, $attempt))
        ->assertOk()
        ->assertSee('<title>Grading… | Recollect</title>', false)
        ->assertSee('AI is checking 2 answers. This can take about a minute.')
        ->assertSee('You can leave this page. Grading will continue.')
        ->assertSee('This is taking longer than usual. Please reload the page later.')
        // 画面と同じ @js() の形で URL を埋め込む
        ->assertSee('attemptStatus({ statusUrl: '.Js::from(route('attempts.status', $attempt)), false)
        ->assertSee('animate-pulse', false);
})->with([AttemptStatus::Pending, AttemptStatus::Grading]);

test('結果画面を開いたら、その回答フォームの下書きを消す', function () {
    // architecture.md 7章: キーはサーバーから渡したセクションと種別で組み立てる
    $attempt = scoredAttempt($this->section, [$this->q1->id => 80]);
    $attempt->update(['mode' => AttemptMode::Weak]);

    $this->get(resultUrl($this->section, $attempt))
        ->assertSee("draftCleaner('quiz:draft:section:{$this->section->id}:weak')", false);
});

test('採点が終わったら、平均点と前回比を出す。点数帯ごとの問題数は出さない', function () {
    scoredAttempt($this->section, [$this->q1->id => 50, $this->q2->id => 60]);
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85, $this->q2->id => 40]);
    $attempt->forceFill(['created_at' => Carbon::parse('2026-10-08 05:32', 'UTC'), 'grading_level' => GradingLevel::Strict])->save();

    $this->get(resultUrl($this->section, $attempt))
        ->assertOk()
        ->assertSee('<title>Results | Recollect</title>', false)
        ->assertSeeInOrder([
            'History', 'Oct 8, 14:32',
            'Results', '>All</span>', '>Strict</span>',
            'Average', '>63</p>', '>▲8</span>',
        ], false)
        // 2026-10-13 決定: 日時はパンくずだけに出し、見出しの下には出さない
        ->assertDontSee('<time', false)
        // 2026-10-13 決定: 点数帯ごとの問題数は出さない
        ->assertDontSee('scored 80+')
        ->assertDontSee('under 60');
});

test('平均点のカードの中に、もう一度挑戦するボタンと、苦手だけ再挑戦するボタンを出す', function () {
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85, $this->q2->id => 40]);

    $this->get(resultUrl($this->section, $attempt))
        ->assertSeeInOrder([
            'Average',
            route('sections.answers.create', [$this->section, 'mode' => 'all']), 'Try again',
            route('sections.answers.create', [$this->section, 'mode' => 'weak']), 'Retry weak (1)',
        ], false);
});

test('問題ごとに、点数・前回比・回答・3つのフィードバックを出す', function () {
    scoredAttempt($this->section, [$this->q1->id => 70]);
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85, $this->q2->id => 40]);

    $this->get(resultUrl($this->section, $attempt))
        ->assertSeeInOrder([
            'Question 1', '>85</span>', '>▲15</span>',
            'TCPとUDPの違いは?', 'Your answer', 'Good points', 'What went wrong', 'Model answer',
            // 2問目は初回
            'Question 2', '>40</span>', '>New</span>',
            'DNSとは?',
        ], false)
        ->assertSee('lang="ja"', false);
});

test('問題のカードは、点数にかかわらず最初から全部開いておく', function () {
    // 2026-10-13 決定
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85, $this->q2->id => 40]);

    $html = (string) $this->get(resultUrl($this->section, $attempt))->getContent();

    expect(substr_count($html, 'x-data="{ open: true }"'))->toBe(2)
        ->and($html)->not->toContain('open: false');
});

test('問題をあとから書き直したり消したりしても、結果画面には回答したときの問題文を出す', function () {
    // architecture.md 2.5 / 4.6(2026-10-13 決定)
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85, $this->q2->id => 40]);
    $this->q1->update(['body' => '書き直した問題文']);
    $this->q2->delete();

    $this->get(resultUrl($this->section, $attempt))
        ->assertOk()
        ->assertSeeInOrder(['Question 1', 'TCPとUDPの違いは?', 'Question 2', 'DNSとは?'])
        ->assertDontSee('書き直した問題文');
});

test('問題を消したあとでも、採点に失敗した挑戦の回答一覧を出せる', function () {
    $attempt = unscoredAttempt($this->section, [$this->q1, $this->q2], AttemptStatus::Failed);
    $this->q1->delete();

    $this->get(resultUrl($this->section, $attempt))
        ->assertOk()
        ->assertSeeInOrder(['Your answers (2)', 'TCPとUDPの違いは?', 'DNSとは?']);
});

test('使用量を一番下に小さく出す。単価が設定されていればコストも出す', function () {
    config([
        'quiz.pricing' => ['claude-sonnet-5-5' => ['input_per_mtok' => '2', 'output_per_mtok' => '10']],
        'quiz.web_search_per_request' => '0.01',
    ]);
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85]);
    $attempt->update(['model' => 'claude-sonnet-5-5', 'input_tokens' => 12340, 'output_tokens' => 3210, 'web_search_requests' => 2]);

    $this->get(resultUrl($this->section, $attempt))
        ->assertSeeInOrder(['Usage: 12,340 input / 3,210 output tokens', '· 2 searches', '· about $0.08'], false);
});

test('単価が設定されていなければ、コストは出さない', function () {
    config(['quiz.pricing' => []]);
    $attempt = scoredAttempt($this->section, [$this->q1->id => 85]);

    $this->get(resultUrl($this->section, $attempt))
        ->assertSee('Usage:')
        ->assertDontSee('about $', false);
});

test('採点に失敗したら、理由は出さず、再採点とセクションへ戻るボタンと、送った回答を出す', function () {
    $attempt = unscoredAttempt($this->section, [$this->q1, $this->q2], AttemptStatus::Failed);
    $attempt->update(['error_message' => 'secret API error']);

    $this->get(resultUrl($this->section, $attempt))
        ->assertOk()
        ->assertSee('<title>Grading failed | Recollect</title>', false)
        ->assertSee('Something went wrong while grading. Your answers are saved, so you can regrade without typing them again.')
        ->assertSeeInOrder([
            'action="'.route('attempts.regrade', $attempt).'"', 'Regrade',
            route('sections.show', $this->section), 'Back to section',
            'Your answers (2)', 'Question 1', 'TCPとUDPの違いは?', '回答 0',
        ], false)
        ->assertDontSee('secret API error');
});
