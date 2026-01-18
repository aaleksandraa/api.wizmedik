<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->google2fa = new Google2FA();
    }

    /**
     * Enable 2FA for user
     */
    public function enable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Generate secret
        $secret = $this->google2fa->generateSecretKey();

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Save to user (but don't enable yet - wait for confirmation)
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Confirm and activate 2FA
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['error' => '2FA not initialized'], 400);
        }

        $secret = decrypt($user->two_factor_secret);

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json(['error' => 'Invalid code'], 401);
        }

        // Enable 2FA
        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json([
            'message' => '2FA enabled successfully',
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Verify 2FA code
        if ($user->two_factor_enabled) {
            $secret = decrypt($user->two_factor_secret);
            $valid = $this->google2fa->verifyKey($secret, $request->code);

            if (!$valid) {
                return response()->json(['error' => 'Invalid code'], 401);
            }
        }

        // Disable 2FA
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json([
            'message' => '2FA disabled successfully',
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        if (!$user->two_factor_enabled) {
            return response()->json(['error' => '2FA not enabled'], 400);
        }

        $secret = decrypt($user->two_factor_secret);

        // Check if it's a recovery code
        if (strlen($request->code) > 6) {
            return $this->verifyRecoveryCode($user, $request->code);
        }

        // Verify regular code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json(['error' => 'Invalid code'], 401);
        }

        return response()->json([
            'valid' => true,
            'message' => '2FA verified successfully',
        ]);
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }
        return $codes;
    }

    /**
     * Verify recovery code
     */
    protected function verifyRecoveryCode($user, $code)
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (!in_array($code, $recoveryCodes)) {
            return response()->json(['error' => 'Invalid recovery code'], 401);
        }

        // Remove used recovery code
        $recoveryCodes = array_diff($recoveryCodes, [$code]);
        $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));
        $user->save();

        return response()->json([
            'valid' => true,
            'message' => 'Recovery code verified successfully',
            'remaining_codes' => count($recoveryCodes),
        ]);
    }

    /**
     * Get recovery codes
     */
    public function getRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json(['error' => '2FA not enabled'], 400);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
