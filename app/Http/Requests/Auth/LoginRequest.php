<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\Auth\DjangoPasswordHasher;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        $identifier = $this->identifier();
        $field = $this->identifierField();
        $user = User::query()
            ->where($field, $identifier)
            ->first();
        $remember = $this->boolean('remember');
        $hasher = app(DjangoPasswordHasher::class);

        if ($user && $user->is_active && $hasher->isLegacyHash($user->password)) {
            if ($this->attemptLegacyPasswordLogin($user)) {
                RateLimiter::clear($this->throttleKey());

                return;
            }
        } elseif (Auth::attempt([
            $field => $identifier,
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ], $remember)) {
            RateLimiter::clear($this->throttleKey());

            return;
        }

        RateLimiter::hit($this->throttleKey());

        $message = $user && ! $user->is_active
            ? 'Este usuario esta inactivo.'
            : trans('auth.failed');

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
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
        return Str::transliterate(Str::lower($this->identifier()).'|'.$this->ip());
    }

    protected function identifier(): string
    {
        return trim($this->string('email')->toString());
    }

    protected function identifierField(): string
    {
        return filter_var($this->identifier(), FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';
    }

    protected function attemptLegacyPasswordLogin(User $user): bool
    {
        $hasher = app(DjangoPasswordHasher::class);
        $plainPassword = $this->string('password')->toString();

        if (! $hasher->check($plainPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($plainPassword);
        $user->save();

        Auth::login($user, $this->boolean('remember'));

        return true;
    }
}
