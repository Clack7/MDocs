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
            $host = empty($_SERVER['HTTP_HOST']) ? 'empty' : $_SERVER['HTTP_HOST'];
            abort(400, 'MDocs configuration for host "' . $host . '" not found.');
        }

        return $next($request);
    }
}
