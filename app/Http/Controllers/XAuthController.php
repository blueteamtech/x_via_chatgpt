<?php

namespace App\Http\Controllers;

use App\Services\X\XOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class XAuthController
{
    public function redirect(Request $request, XOAuthService $oauth): RedirectResponse|View
    {
        try {
            $authorization = $oauth->authorization();
        } catch (InvalidArgumentException $exception) {
            return view('auth.x-error', [
                'message' => $exception->getMessage().' Run php artisan x:status for the X Developer Portal fields.',
            ]);
        }

        $request->session()->put('x_oauth_state', $authorization['state']);
        $request->session()->put('x_oauth_verifier', $authorization['verifier']);

        if (! $request->session()->has('url.intended') && $request->headers->get('referer')) {
            $request->session()->put('url.intended', $request->headers->get('referer'));
        }

        return redirect()->away($authorization['url']);
    }

    public function callback(Request $request, XOAuthService $oauth): RedirectResponse|View
    {
        if ($request->filled('error')) {
            return view('auth.x-error', [
                'message' => $request->string('error_description')->toString()
                    ?: $request->string('error')->toString(),
            ]);
        }

        $state = (string) $request->session()->pull('x_oauth_state');
        $verifier = (string) $request->session()->pull('x_oauth_verifier');

        if ($state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            return view('auth.x-error', [
                'message' => 'The X login state did not match. Start again from ChatGPT or this site.',
            ]);
        }

        $code = (string) $request->query('code');

        if ($code === '' || $verifier === '') {
            return view('auth.x-error', [
                'message' => 'X did not return an authorization code.',
            ]);
        }

        try {
            $token = $oauth->exchangeCode($code, $verifier);
            $user = $oauth->upsertUser($token);
        } catch (\Throwable $exception) {
            report($exception);

            return view('auth.x-error', [
                'message' => $exception->getMessage(),
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
