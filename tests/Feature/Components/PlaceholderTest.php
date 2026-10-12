<?php

// 空の状態とスケルトン(design-guide.md 7.8 / 7.9)

test('空の状態は、点線の枠に、説明と次の行動のボタンを出す', function () {
    $this->blade('<x-ui.empty-state message="No questions yet." action="Add question" action-href="/sections/1/questions/create" />')
        ->assertSee('border-dashed', false)
        ->assertSeeInOrder(['<svg', 'No questions yet.', 'href="/sections/1/questions/create"', 'Add question'], false);
});

test('ボタンを渡さなければ、説明だけを出す', function () {
    $this->blade('<x-ui.empty-state message="No attempts yet." />')
        ->assertSee('No attempts yet.')
        ->assertDontSee('<a', false);
});

test('スケルトンは、点滅する薄いグレーのブロックで、読み上げない', function () {
    $this->blade('<x-ui.skeleton class="h-4 w-32" />')
        ->assertSee('animate-pulse', false)
        ->assertSee('bg-neutral-100', false)
        ->assertSee('h-4 w-32', false)
        ->assertSee('aria-hidden="true"', false);
});
