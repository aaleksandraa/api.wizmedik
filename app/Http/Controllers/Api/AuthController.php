<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new patient user
     */
    public function register(Request $request)
    {
        $request->validate([
            'ime' => 'required|string|max:255',
            'prezime' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telefon' => 'nullable|string|max:20',
            'grad' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->ime . ' ' . $request->prezime,
            'ime' => $request->ime,
            'prezime' => $request->prezime,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefon' => $request->telefon,
            'grad' => $request->grad,
        ]);

        // Assign patient role by default
        $user->assignRole('patient');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registracija uspješna',
            'user' => [
                'id' => $user->id,
                'ime' => $user->ime,
                'prezime' => $user->prezime,
                'email' => $user->email,
                'telefon' => $user->telefon,
                'grad' => $user->grad,
                'role' => $user->getRoleNames()->first(),
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Login attempt START', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'has_password' => !empty($request->password),
            'password_length' => strlen($request->password ?? ''),
            'all_data' => $request->all(),
        ]);

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
        } catch (\Exception $e) {
            Log::error('Login validation failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);
            throw $e;
        }

        // Account lockout: Check if too many failed attempts
        $throttleKey = 'login:' . $request->ip() . ':' . $request->email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            // Log security event
            Log::channel('security')->warning('Account locked due to too many failed login attempts', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'locked_for_seconds' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => ["Previše neuspješnih pokušaja prijave. Pokušajte ponovo za " . ceil($seconds / 60) . " minuta."],
            ]);
        }

        // Attempt login
        Log::info('Attempting Auth::attempt', [
            'email' => $request->email,
            'credentials' => $request->only('email'),
        ]);

        $authAttempt = Auth::attempt($request->only('email', 'password'));

        Log::info('Auth::attempt result', [
            'success' => $authAttempt,
            'email' => $request->email,
        ]);

        if (!$authAttempt) {
            // Increment failed attempts
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            // Log failed login attempt
            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts' => RateLimiter::attempts($throttleKey),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Neispravni pristupni podaci.'],
            ]);
        }

        // Clear failed attempts on successful login
        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        // Log successful login
        Log::channel('security')->info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Prijava uspješna',
            'user' => [
                'id' => $user->id,
                'ime' => $user->ime,
                'prezime' => $user->prezime,
                'email' => $user->email,
                'telefon' => $user->telefon,
                'grad' => $user->grad,
                'role' => $user->getRoleNames()->first(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Odjava uspješna',
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'ime' => $user->ime,
                'prezime' => $user->prezime,
                'email' => $user->email,
                'telefon' => $user->telefon,
                'datum_rodjenja' => $user->datum_rodjenja,
                'adresa' => $user->adresa,
                'grad' => $user->grad,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link za resetovanje lozinke je poslat na vaš email.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Lozinka je uspješno resetovana.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
