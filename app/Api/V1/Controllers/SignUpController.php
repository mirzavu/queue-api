<?php

namespace App\Api\V1\Controllers;

use Config;
use Log;
use Validator;
use App\User;
use App\Http\Requests;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignUpController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
    }

    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        Log::info('ddfdf');
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:150|unique:users',
            'name' => 'max:120',
            'password' => 'required|min:6|max:100'
        ]);

        if ($validator->fails()) {
            Log::info(response()->json(['status' => 'fail', 'error' => $validator->errors()], 422));
            return response()->json(['status' => 'fail', 'error' => $validator->errors()], 422);
        }

        $user = new User($request->all());
        if(!$user->save()) {
            throw new HttpException(500);
        }

        if(!Config::get('boilerplate.sign_up.release_token')) {
            return response()->json([
                'status' => 'ok'
            ], 201);
        }

        $token = $JWTAuth->fromUser($user);
        return response()->json([
            'status' => 'ok',
            'id' => $user->id,
            'token' => $token,
            'email' => $user->email,
            'name' => $user->name
        ], 201);
    }

    public function verifyToken(JWTAuth $JWTAuth)
    {
        try {
            $user = $JWTAuth->parseToken()->authenticate();
            if (! $user = $JWTAuth->parseToken()->authenticate()) {
                Log::info('aaaaaa');
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
             return response()->json(['token_expired'], $e->getStatusCode());
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {Log::info('aaaaaa');
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {Log::info('aaaaaa');
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }
}
