<?php

namespace App\Controllers\Api;

use App\Request\AuthRequest;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Respond;
use Core\Http\Request;
use Core\Support\Time;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthController extends Controller
{
    /**
     * Mints the session token.
     *
     * A "remember" session gets JWT_REMEMBER_EXP instead of JWT_EXP, and says so
     * in its own `rmb` claim so that refresh() can renew it for the same length
     * without the browser having to be trusted about which kind it holds.
     */
    private function issue(bool $remember): string
    {
        $time = Time::factory()->getTimestamp();
        $lifetime = $remember
            ? intval(env('JWT_REMEMBER_EXP', 2592000))
            : intval(env('JWT_EXP', 3600));

        return JWT::encode(
            [
                'iat' => $time,
                'exp' => $time + $lifetime,
                'iss' => base_url(),
                'sub' => strval(auth()->id()),
                'rmb' => $remember,
            ],
            env('JWT_KEY'),
            env('JWT_ALGO', 'HS256')
        );
    }

    public function login(AuthRequest $request, JsonResponse $json): JsonResponse
    {
        $valid = $request->validated();

        if ($valid->fails()) {
            return $json->errorBadRequest($valid->messages());
        }

        try {
            if (!Auth::attempt($valid->only(['email', 'password']))) {
                throw new Exception('Invalid credentials');
            }
        } catch (Throwable) {
            return $json->error(Respond::HTTP_UNAUTHORIZED);
        }

        if (!auth()->user()->isActive()) {
            return $json->errorBadRequest(['user not active.']);
        }

        if (!env('JWT_KEY')) {
            return $json->errorBadRequest(['JWT Key not found!.']);
        }

        return $json->successOK([
            'token' => $this->issue(boolval($valid->get('remember'))),
            'user' => Auth::user()->only(['name', 'email'])
        ]);
    }

    /**
     * Hands back a fresh token to a caller that already holds a valid one, so a
     * dashboard that is still being used keeps sliding forward instead of
     * expiring mid-edit. AuthMiddleware has already proved the bearer token, and
     * DashboardMiddleware has already rejected the access-key path - a guest key
     * must never be able to mint an admin session.
     */
    public function refresh(Request $request, JsonResponse $json): JsonResponse
    {
        if (!env('JWT_KEY')) {
            return $json->errorBadRequest(['JWT Key not found!.']);
        }

        $remember = false;

        try {
            $token = JWT::decode(
                $request->bearerToken(),
                new Key(env('JWT_KEY'), env('JWT_ALGO', 'HS256'))
            );
            $remember = boolval($token->rmb ?? false);
        } catch (Throwable) {
            return $json->error(Respond::HTTP_UNAUTHORIZED);
        }

        return $json->successOK([
            'token' => $this->issue($remember),
            'user' => Auth::user()->only(['name', 'email'])
        ]);
    }
}
