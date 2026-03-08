<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

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
            // Validate the request including avatar
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'username' => 'required|string|max:255|unique:users',
                'password' => 'required|string|min:8',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Max 2MB
            ]);

            // Handle avatar upload
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                // Validate file upload
                if ($file->isValid()) {
                    // Generate unique filename
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $fileName = time() . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();

                    // Create directory if it doesn't exist
                    $uploadPath = public_path('uploads/avatars');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    // Move file to uploads directory
                    $file->move($uploadPath, $fileName);

                    $avatarPath = $fileName;
                }
            }

            // Create user with avatar
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath, // Add this column to your users table
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            // Prepare user data with avatar URL
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar ? url('uploads/avatars/' . $user->avatar) : null,
            ];

            return response()->json([
                'status' => 201,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $userData,
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

    //update profile
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            // Check if user is authenticated
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthenticated',
                    'errors' => ['auth' => ['You need to login first']]
                ], 401);
            }

            // Log incoming request data for debugging
            Log::info('Profile update request for user: ' . $user->id, $request->all());

            $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            $updated = false;
            $updatedFields = [];

            // Update fields if present in request
            if ($request->has('first_name') && !empty($request->first_name)) {
                $user->first_name = $request->first_name;
                $updated = true;
                $updatedFields[] = 'first_name';
            }

            if ($request->has('last_name') && !empty($request->last_name)) {
                $user->last_name = $request->last_name;
                $updated = true;
                $updatedFields[] = 'last_name';
            }

            if ($request->has('email') && !empty($request->email)) {
                $user->email = $request->email;
                $updated = true;
                $updatedFields[] = 'email';
            }

            if ($request->has('username') && !empty($request->username)) {
                $user->username = $request->username;
                $updated = true;
                $updatedFields[] = 'username';
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                if ($file->isValid()) {
                    // Delete old avatar if exists
                    if ($user->avatar) {
                        $oldAvatarPath = public_path('uploads/avatars/' . $user->avatar);
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                            Log::info('Deleted old avatar: ' . $user->avatar);
                        }
                    }

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $fileName = time() . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();

                    $uploadPath = public_path('uploads/avatars');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    $file->move($uploadPath, $fileName);
                    $user->avatar = $fileName;
                    $updated = true;
                    $updatedFields[] = 'avatar';

                    Log::info('New avatar uploaded: ' . $fileName);
                } else {
                    Log::error('Avatar file is not valid');
                }
            }

            // Save only if there are changes
            if ($updated) {
                $user->save();
                Log::info('User profile updated. Fields: ' . implode(', ', $updatedFields));
            } else {
                Log::info('No changes detected for user profile');
            }

            // Refresh user model to get latest data
            $user = $user->fresh();

            // Prepare response
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar ? url('uploads/avatars/' . $user->avatar) : null,
            ];

            return response()->json([
                'status' => 200,
                'message' => $updated ? 'Profile updated successfully' : 'No changes were made',
                'updated_fields' => $updatedFields,
                'data' => $userData,
                'errors' => []
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Profile update validation error: ' . json_encode($e->errors()));

            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 500,
                'message' => 'Profile update failed: ' . $e->getMessage(),
                'errors' => ['server' => ['Internal server error: ' . $e->getMessage()]]
            ], 500);
        }
    }
}
