<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * フォントの読み込みタグ(design-guide.md 3.1)。
 *
 * Laravel の @fonts は、フォントの CSS(@font-face)を毎回ページの中に書き出す。
 * 日本語フォントは文字の範囲ごとに約120個の定義があり、どのページの HTML も約300KB 重くなってしまう。
 * そこで、ビルド時に作られる同じ内容の CSS ファイルを <link> で読み、ブラウザに1回だけ取得・キャッシュさせる。
 */
final class FontTags
{
    public static function html(string $manifestPath, string $buildUrl = '/build'): HtmlString
    {
        if (! is_file($manifestPath)) {
            return new HtmlString('');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $tags = [];

        // 英数字の Inter など、先読みを指定したフォントだけを先に取りに行かせる
        foreach ($manifest['preloads'] ?? [] as $preload) {
            $tags[] = sprintf(
                '<link rel="preload" as="font" href="%s" type="%s" crossorigin>',
                e($buildUrl.'/'.$preload['file']),
                e($preload['type'] ?? 'font/woff2'),
            );
        }

        if (isset($manifest['style']['file'])) {
            $tags[] = sprintf('<link rel="stylesheet" href="%s">', e($buildUrl.'/'.$manifest['style']['file']));
        }

        return new HtmlString(implode("\n", $tags));
    }
}
