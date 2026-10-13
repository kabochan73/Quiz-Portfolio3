{{--
    日時の表示(architecture.md 2.7、requirements.md 5.5)。
    日時は UTC で保存し、画面に出すときだけ表示用のタイムゾーン(config の quiz.display_timezone。既定は Asia/Tokyo)に変える。
    変換は App\Support\DisplayTime にまとめ、画面ごとに書かないようにする。
    format: short(Oct 7。一覧など)/ long(Oct 8, 14:32。結果画面など)
--}}
@props([
    'value',
    'format' => 'short',
])

<time datetime="{{ \App\Support\DisplayTime::local($value)->toIso8601String() }}" {{ $attributes->class('tabular-nums') }}>{{ \App\Support\DisplayTime::format($value, $format) }}</time>
