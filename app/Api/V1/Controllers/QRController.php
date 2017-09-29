<?php

namespace App\Api\V1\Controllers;

use Log;
use Validator;
use App\User;
use App\Queue;
use Illuminate\Http\Request;
use App\Http\Requests;
use Tymon\JWTAuth\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;

class QRController extends Controller
{

	protected $user;

	public function __construct(JWTAuth $JWTAuth)
    {
        $this->middleware('cors');
        try {
        	Log::info('aa');
            if (! $this->user = $JWTAuth->parseToken()->authenticate()) {
                Log::info('aaaaaa');
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        	Log::info('token expired');
             return response()->json(['token_expired'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {Log::info('aaaaaa');
            return response()->json(['token_invalid'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            Log::info('aaaaaa');
            return response()->json(['token_absent'], 400);
        }
    }

    public function generateQR(Request $request)
    {
    	Log::info($this->user);
    	$queue = Queue::firstOrNew(['user_id' => $this->user->id]);
    	$queue->file = $this->user->id.'_qr.png';
    	if(!file_exists($queue->file))
		{
			$queue->data = md5(uniqid($this->user->id, true));
			QrCode::format('png')->size(500)->generate($queue->data, public_path() . '/uploads/qr/'.$queue->file );
			$queue->save();
		}
        $cur_token = $queue->cur_token? $queue->cur_token:'--';
    	
		return response()->json([
            'status' => 'ok',
            'qr' => $queue->file,
            'token' => $cur_token
        ], 201);
    }
}