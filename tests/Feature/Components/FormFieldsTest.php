<?php

// 入力まわりの共通コンポーネント(design-guide.md 7.2 / 7.11)。見た目は /dev/components(5-6)で確かめる

test('入力欄は、上にラベルを置き、ラベルと入力欄を結びつける', function () {
    // design-guide.md 8章: ラベルと入力欄は for / id で関連付ける
    $this->blade('<x-ui.input name="name" label="Name" value="Networking" />')
        ->assertSeeInOrder(['<label for="name"', 'Name', '<input', 'id="name"', 'name="name"', 'value="Networking"'], false);
});

test('入力の文字は16px(iOS で画面が拡大されないように)', function () {
    $this->blade('<x-ui.input name="name" label="Name" />')->assertSee('text-base', false);
});

test('エラーがあれば、入力欄を赤くし、直下にメッセージを出す', function () {
    $this->withViewErrors(['name' => 'The name field is required.'])
        ->blade('<x-ui.input name="name" label="Name" />')
        ->assertSee('border-rose-500', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="name-error"', false)
        ->assertSeeInOrder(['<input', 'id="name-error"', 'The name field is required.'], false);
});

test('エラーがなければ、エラーの表示は出さない', function () {
    $this->blade('<x-ui.input name="name" label="Name" />')
        ->assertDontSee('border-rose-500', false)
        ->assertDontSee('aria-invalid', false);
});

test('配列の名前でも、その項目のエラーを出す', function () {
    // 回答フォームの answers[0][body] など
    $this->withViewErrors(['answers.0.body' => 'Please answer all questions.'])
        ->blade('<x-ui.textarea name="answers[0][body]" label="Question 1" />')
        ->assertSee('id="answers-0-body"', false)
        ->assertSee('Please answer all questions.');
});

test('テキストエリアは、上限の文字数を渡すと右下に文字数を出す', function () {
    // design-guide.md 7.2: 「120 / 2000」のように出す
    $this->blade('<x-ui.textarea name="body" label="Question" value="TCPとUDP" maxlength="2000" />')
        ->assertSee('maxlength="2000"', false)
        ->assertSeeInOrder(['>TCPとUDP</textarea>', '7</span> / 2000'], false);
});

test('テキストエリアは、上限を渡さなければ文字数を出さない', function () {
    $this->blade('<x-ui.textarea name="body" label="Question" />')
        ->assertDontSee('maxlength', false)
        ->assertDontSee('x-text', false);
});

test('セレクトは、選ばれている選択肢に印を付ける', function () {
    $this->blade('<x-ui.select name="level" label="Level" :options="$options" value="normal" />', [
        'options' => ['easy' => 'Easy', 'normal' => 'Normal'],
    ])
        ->assertSee('<option value="easy" >Easy</option>', false)
        ->assertSee('<option value="normal" selected>Normal</option>', false);
});

test('切り替えボタンは、見えないラジオボタンを並べ、選ばれているものに印を付ける', function () {
    // design-guide.md 7.11: キーボードの矢印キーでも選べるよう、中身は普通のラジオボタン
    $this->blade('<x-ui.radio-group name="grading_level" label="Grading level" :options="$options" value="normal" />', [
        'options' => ['easy' => 'Easy', 'normal' => 'Normal', 'strict' => 'Strict'],
    ])
        ->assertSeeInOrder(['<legend', 'Grading level', 'value="easy"', 'Easy', 'value="normal"', 'checked', 'Normal', 'value="strict"', 'Strict'], false)
        ->assertSee('sr-only', false)
        ->assertSee('peer-checked:bg-ink', false);

    // 選ばれているのは1つだけ(クラス名の peer-checked は数えない)
    $html = (string) $this->blade('<x-ui.radio-group name="g" label="G" :options="$o" value="b" />', ['o' => ['a' => 'A', 'b' => 'B']]);
    expect(preg_match_all('/\schecked\s*>/', $html))->toBe(1);
});

test('送信し直したときは、入力した値を残す', function () {
    // 入力エラーで画面に戻ったとき、書いた内容が消えないように
    request()->setLaravelSession(tap(app('session.store'))->put('_old_input', ['name' => 'Typed']));

    $this->blade('<x-ui.input name="name" label="Name" value="Saved" />')->assertSee('value="Typed"', false);
});
