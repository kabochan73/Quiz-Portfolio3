<?php

use App\Support\FontTags;

// フォントの読み込みタグ。@fonts のように CSS をページに書き出さず、ファイルを <link> で読む

function fontManifest(array $manifest): string
{
    $path = tempnam(sys_get_temp_dir(), 'fonts');
    file_put_contents($path, json_encode($manifest));

    return $path;
}

test('先読みのタグと、フォントの CSS ファイルへの link を出す', function () {
    $path = fontManifest([
        'style' => ['file' => 'assets/fonts-abc.css'],
        'preloads' => [['file' => 'assets/inter-400.woff2', 'type' => 'font/woff2']],
    ]);

    expect((string) FontTags::html($path, 'https://example.com/build'))->toBe(implode("\n", [
        '<link rel="preload" as="font" href="https://example.com/build/assets/inter-400.woff2" type="font/woff2" crossorigin>',
        '<link rel="stylesheet" href="https://example.com/build/assets/fonts-abc.css">',
    ]));
});

test('CSS の中身(@font-face)はページに書き出さない', function () {
    $path = fontManifest(['style' => ['file' => 'assets/fonts-abc.css', 'familyStyles' => ['inter' => '@font-face { }']]]);

    expect((string) FontTags::html($path))->not->toContain('@font-face')
        ->and((string) FontTags::html($path))->not->toContain('<style');
});

test('ビルドしていなければ、何も出さない', function () {
    expect((string) FontTags::html('/no/such/fonts-manifest.json'))->toBe('');
});
