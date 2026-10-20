<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Mendukung alias domain demo (bengkel.com, erp.test, example.com) dan username langsung (admin, kasir, mekanik).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $input = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        // 1. Coba login langsung dengan input pengguna
        $authenticated = Auth::attempt(['email' => $input, 'password' => $password], $this->boolean('remember'));

        // 2. Jika gagal, coba permutasi domain demo dan password demo (password / password123)
        if (! $authenticated) {
            $prefix = str_contains($input, '@') ? explode('@', $input)[0] : $input;
            $candidateEmails = array_unique([
                $input,
                $prefix . '@bengkel.com',
                $prefix . '@erp.test',
                $prefix . '@example.com',
            ]);

            $candidatePasswords = array_unique([
                $password,
                'password',
                'password123',
            ]);

            // Hanya coba fallback password demo jika password yang diinput adalah variasi password demo
            $passwordsToTry = in_array($password, ['password', 'password123'], true)
                ? $candidatePasswords
                : [$password];

            foreach ($candidateEmails as $candEmail) {
                foreach ($passwordsToTry as $candPass) {
                    if (Auth::attempt(['email' => $candEmail, 'password' => $candPass], $this->boolean('remember'))) {
                        $authenticated = true;
                        break 2;
                    }
                }
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
