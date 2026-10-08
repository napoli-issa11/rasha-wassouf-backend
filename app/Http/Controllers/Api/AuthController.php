<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate Admin User & Return API Token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create Sanctum API token
        $token = $user->createToken('admin-access-token')->plainTextToken;

        return response()->json([
            'message' => 'Authentication successful.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Terminate Admin Session / Revoke Tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return Authenticated User Info.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Update Authenticated Admin Credentials (Email and/or Password).
     * Protected for Admin CMS (auth:sanctum).
     */
    public function updateCredentials(Request $request): JsonResponse
    {
        $user = $request->user();

        // Support both standard (email, password, password_confirmation)
        // and prefixed keys (new_email, new_password, new_password_confirmation)
        $email = $request->input('new_email', $request->input('email'));
        $password = $request->input('new_password', $request->input('password'));
        $passwordConfirmation = $request->input(
            'new_password_confirmation',
            $request->input('password_confirmation', $request->input('confirm_new_password'))
        );

        $data = [];
        if ($email !== null && trim($email) !== '') {
            $data['email'] = trim($email);
        }
        if ($password !== null && trim($password) !== '') {
            $data['password'] = $password;
            $data['password_confirmation'] = $passwordConfirmation;
        }

        if (empty($data)) {
            return response()->json([
                'message' => 'Please provide a new email or password to update.',
            ], 422);
        }

        $rules = [];
        if (isset($data['email'])) {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ];
        }
        if (isset($data['password'])) {
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'confirmed',
            ];
        }

        $validated = validator($data, $rules, [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The new password must be at least 8 characters.',
        ])->validate();

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Admin credentials updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
