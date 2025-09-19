<?php

namespace App\Http\Middleware;

use Closure;

class VerifyApiToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('X-CRM-TOKEN');
        if ($token !== config('osiris.crm-api-token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
