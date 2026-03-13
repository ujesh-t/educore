<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $modules  Comma-separated list of module keys required for this route
     */
    public function handle(Request $request, Closure $next, string $modules): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Super admins have access to all modules
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Parse required modules (can be comma-separated)
        $requiredModules = array_map('trim', explode(',', $modules));

        // Check if user's school has any of the required modules enabled
        $hasAccess = false;
        foreach ($requiredModules as $moduleKey) {
            if ($user->canAccessModule($moduleKey)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            // Check if school exists but module is not enabled
            if ($user->school) {
                return response()->json([
                    'success' => false,
                    'message' => 'This module is not enabled for your school. Please contact your administrator.',
                    'error_code' => 'MODULE_NOT_ENABLED',
                ], 403);
            }

            // User has no school assigned
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to any school. Please contact your administrator.',
                'error_code' => 'NO_SCHOOL_ASSIGNED',
            ], 403);
        }

        return $next($request);
    }
}
