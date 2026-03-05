<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, return null (no redirect) and let the exception handler handle it
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // For web requests, redirect to login page
        return route('login');
    }

    /**
     * Handle unauthenticated user
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'status' => 401,
                'message' => 'Unauthenticated. Please login to continue.',
                'errors' => ['auth' => ['You need to be logged in to access this resource']]
            ], 401));
        }

        parent::unauthenticated($request, $guards);
    }
}
