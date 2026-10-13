<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ログインの入力チェックと認証(requirements.md 3.1)。
 */
class LoginRequest extends FormRequest
{
    // ログインの試行は1分あたり6回まで(requirements.md 3.1)
    private const MAX_ATTEMPTS = 6;

    private const DECAY_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * 認証する。失敗したら、どちらの項目が違うかは出さず、1つのエラーにまとめる(screens.md 2.1)。
     * 認証の失敗と回数の上限は、入力欄ではなくフォームの上に出すので、項目名ではなく credentials に付ける。
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // 「ログイン状態を保持」のチェックボックスは置かず、常に保持する(requirements.md 3.1)。
        // 保持する期間(30日)は config/auth.php の guards.web.remember で決めている
        if (! Auth::attempt($this->only('email', 'password'), remember: true)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages(['credentials' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * 試行回数の上限に達していたら、何秒後に再試行できるかをエラーにする(requirements.md 3.1)。
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'credentials' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    /**
     * メールアドレスと IP アドレスの組み合わせごとに数える。
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
