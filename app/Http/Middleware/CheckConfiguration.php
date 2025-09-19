<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ConfigurationValidator;
use App\Exceptions\ConfigurationException;

class CheckConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $service
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $service = null)
    {
        // If a specific service is specified, validate it
        if ($service) {
            try {
                ConfigurationValidator::validateService($service);
            } catch (ConfigurationException $e) {
                // For API routes, throw the exception to be handled by the exception handler
                if ($request->expectsJson() || $request->is('api/*')) {
                    throw $e;
                }
                
                // For web routes, redirect with error message
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        
        return $next($request);
    }
}