<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Setting;
use App\Traits\SendsNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\PasswordReset;
use Str;

class AuthController extends Controller
{
    use SendsNotifications;
    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditLog::log('login_failed', null, "Failed login attempt for email: {$request->email}");
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administration.',
            ], 403);
        }

        // Delete any existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLog::log('login', $user, "User logged in");

        // Eager load school with its enabled modules
        $user->load(['role', 'school.enabledModules']);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
        ]);
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        // Check if registration is allowed
        $allowRegistration = Setting::get('allow_registration', 'false');
        if ($allowRegistration !== 'true') {
            return response()->json([
                'success' => false,
                'message' => 'Registration is currently disabled. Please contact administration.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Only allow certain roles for self-registration
        $allowedRoles = ['student', 'parent', 'teacher'];
        if (!in_array($request->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role selected',
            ], 422);
        }

        $role = Role::where('name', $request->role)->first();

        if (!$role || !$role->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Selected role is not available',
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'is_active' => true,
        ]);

        AuditLog::log('register', $user, "New user registered: {$user->email}");

        // Send welcome email
        $this->sendWelcomeNotification(
            $user->email,
            $user->name,
            [
                'email' => $user->email,
                'password' => $request->password,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please login.',
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete current token
        $user->currentAccessToken()->delete();

        AuditLog::log('logout', $user, "User logged out");

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->load('role'),
            ],
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate reset token
        $token = Password::createToken($user);

        // Send password reset email using Resend
        $result = $this->sendPasswordResetNotification($user->email, $user->name, $token);

        if ($result['success']) {
            AuditLog::log('password_reset_requested', null, "Password reset requested for: {$request->email}");

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send password reset email',
        ], 500);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                AuditLog::log('password_reset', $user, "Password reset successfully");
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => __($status),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldValues = $user->only(['name', 'phone', 'date_of_birth', 'gender', 'address']);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('date_of_birth')) {
            $user->date_of_birth = $request->date_of_birth;
        }
        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }
        if ($request->has('address')) {
            $user->address = $request->address;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $path = $avatar->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        AuditLog::log(
            'profile_update',
            $user,
            "User profile updated",
            $oldValues,
            $user->only(['name', 'phone', 'date_of_birth', 'gender', 'address'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->load('role'),
            ],
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        AuditLog::log('password_change', $user, "User changed password");

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
