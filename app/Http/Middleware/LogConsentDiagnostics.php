<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Temporary instrumentation for diagnosing the ChatGPT consent handshake.
 * Remove once the authorization flow is confirmed working.
 */
class LogConsentDiagnostics
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $fingerprint = fn (?string $value) => $value === null || $value === ''
            ? 'empty'
            : substr(hash('sha256', $value), 0, 8);

        $before = [
            'method' => $request->method(),
            'spoofed' => $request->input('_method'),
            'session_id' => $fingerprint($request->session()->getId()),
            'session_authToken' => $fingerprint($request->session()->get('authToken')),
            'session_has_authRequest' => $request->session()->has('authRequest'),
            'posted_auth_token' => $fingerprint($request->input('auth_token')),
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'via_remember' => Auth::viaRemember(),
            'cookies' => array_keys($request->cookies->all()),
        ];

        $response = $next($request);

        Log::warning('consent-diagnostics', $before + [
            'status' => $response->getStatusCode(),
            'session_id_after' => $fingerprint($request->session()->getId()),
            'session_authToken_after' => $fingerprint($request->session()->get('authToken')),
        ]);

        return $response;
    }
}
