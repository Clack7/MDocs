<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHostConfig
{
    public function handle(Request $request, Closure $next)
    {
        // Validate if host config is found and loaded
        if (empty(config('mdocs.dir'))) {
            abort(500, 'MDocs host configuration not found.');
        }

        return $next($request);
    }
}
