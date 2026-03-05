<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found',
                    'errors' => ['email' => ['No account found with this email']]
                ], 404);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid credentials',
                    'errors' => ['password' => ['Incorrect password']]
                ], 401);
            }

            // Delete old tokens (optional)
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'profile_image_url' => $user->profile_image ? asset('uploads/profiles/' . $user->profile_image) : null,
                    ],
                    'token' => $token,
                ],
                'errors' => []
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Login failed',
                'errors' => ['server' => ['Internal server error: ' . $e->getMessage()]]
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Revoke the token that was used to authenticate the current request
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully logged out',
                'errors' => []
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Logout failed',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'status' => 200,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'profile_image_url' => $user->profile_image ? asset('uploads/profiles/' . $user->profile_image) : null,
                        'created_at' => $user->created_at,
                    ]
                ],
                'errors' => []
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get user info',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'username' => 'required|string|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'full_name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'status' => 201,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'username' => $user->username,
                    ],
                    'token' => $token,
                ],
                'errors' => []
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Registration failed',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }
}

