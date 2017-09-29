<?php

namespace App\Api\V1\Controllers;

use Config;
use Log;
use Validator;
use App\User;
use App\Otp;
use App\Http\Requests;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OTPController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
    }

    public function sendOTP(Request $request)
    {
        // Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'error' => $validator->errors()], 422);
        }
        else
        {
            $end_point = 'https://api.textlocal.in/send/?apikey=ZZeKtShm5CI-9E3QPOKTQJ5fB1SswBeWLrAyjLQBZf&';
            $otp =  rand(pow(10, 3), pow(10, 4)-1);
            $message = urlencode('Your%20OTP verification number is '.$otp);
            $url = $end_point.'numbers='.$request->mobile.'&'.'message='.$message;
            $otp_row = OTP::firstorCreate(['mobile' => $request->mobile]);
            $otp_row->otp = $otp;
            $otp_row->save();
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            // $url = urlencode($url);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec ($ch);
            Log::info($output);
            return response()->json([
                'status' => 'ok'
            ], 201);
        }

    }

    public function verifyOTP(Request $request, JWTAuth $JWTAuth)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:4'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'error' => $validator->errors()], 422);
        }
        else
        {
            
            $otp_row = OTP::where(['mobile' => $request->mobile, 'otp' => $request->otp])->get();

            if(count($otp_row))
            {

                $user = User::firstorCreate(['mobile' => $request->mobile]);
                Log::info($user);
                if(!$user->save()) {
                    throw new HttpException(500);
                }
                $navigate = empty($user->email)? 'Register':'Home';
            
                $token = $JWTAuth->fromUser($user);
                return response()->json([
                    'status' => 'ok',
                    'id' => $user->id,
                    'token' => $token,
                    'navigate' => $navigate
                ], 201);
            }
            return response()->json(['status' => 'fail', 'error' => ['otp' => 'Incorrect OTP']], 422);
            
        }
    }
}
