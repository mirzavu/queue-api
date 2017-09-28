<?php

namespace App\Api\V1\Controllers;

use Config;
use Log;
use Validator;
use App\User;
use App\Http\Requests;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignUpController extends Controller
{

    protected $user;
    public function __construct(JWTAuth $JWTAuth)
    {
        $this->middleware('cors');
        try {
            if (! $this->user = $JWTAuth->parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
             return response()->json(['token_expired'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], 400);
        }
    }

    public function signUp(SignUpRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:150|unique:users',
            'name' => 'max:120'
        ]);

        if ($validator->fails()) {
            Log::info(response()->json(['status' => 'fail', 'error' => $validator->errors()], 422));
            return response()->json(['status' => 'fail', 'error' => $validator->errors()], 422);
        }

        Log::info($request->all());

        
        $user = $this->user;
        $user->fill($request->all());
        if(!$user->save()) {
            throw new HttpException(500);
        }
        return response()->json([
            'status' => 'ok',
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name
        ], 201);
    }

    public function verifyToken()
    {
        $user = $this->user;
        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'), 201);
    }

    public function savePushToken(Request $request)
    {
        Log::info('ttt');
        $this->user->push_token = $request->token;
        $this->user->save();
        return response()->json([
            'status' => 'ok'
        ], 200);
    }
}
