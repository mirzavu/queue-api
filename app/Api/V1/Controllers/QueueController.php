<?php

namespace App\Api\V1\Controllers;

use Log;
use Validator;
use App\User;
use App\Queue;
use App\Holder;
use Illuminate\Http\Request;
use App\Http\Requests;
use Tymon\JWTAuth\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;
use App\Events\eventTrigger;

class QueueController extends Controller
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

    public function getTokenData(Request $request)
    {
    	Log::info($this->user);
        Log::info($request);
        $queue = Queue::where('data', $request->qr)->first();
        Log::info($queue);
        $holder = Holder::firstorNew([
            'user_id' => $this->user->id, 
            'queue_id' => $queue->id]
        );

        if(!$holder->exists)
        {
            $last_token = Holder::where('queue_id', $queue->id)
                ->orderBy('token', 'DESC')
                ->take(1)
                ->value('token');
            if($last_token)
            {
                $holder->token = $last_token+1;
            }
            else
            {
                $holder->token = 1;
                $queue->cur_token = 1; //start queue by setting its cur token to 1
                $queue->save();
            }
        }
        $holder->save();
    	
		return response()->json([
            'status' => 'ok',
            'queue_id' => $queue->id,
            'token' => $holder->token,
            'current' =>$queue->cur_token
        ], 201);
    }

    public function leaveQueue(Request $request)
    {
        Holder::where('user_id', $this->user->id)->delete();
        return response()->json([
            'status' => 'ok'
        ], 201);
    }

    public function nextToken(Request $request)
    {
        $push_list = array();
        $queue = $this->user->queue()->first();
        $cur_token = $queue->cur_token;
        if(empty($cur_token))
        {
            return response()->json([
                'status' => 'not started'
            ], 201); 
        }
        
        //get next token in holders list
        $next_list = $queue->holders()->with('user')->where('token', '>' ,$cur_token)->orderBy('token', 'asc')->take(3)->get();
        if($next_list->count())
        {
            $next_holder = $next_list->shift();
            $this->sendPushNotification(json_encode([
                "to" => $next_holder->user->push_token,
                "title" => "Queue",
                "body"=> "Your turn is up"
            ]));
            $queue->cur_token = $next_holder->token;
            $queue->save();

            event(new eventTrigger($queue));

            foreach ($next_list as $holder) {
                array_push($push_list,[
                    "to" => $holder->user->push_token,
                    "title" => "Token ".$queue->cur_token." called",
                    "body" => "Please acknowledge at the helpdesk"
                    ]);
            }
            $this->sendPushNotification(json_encode($push_list));
            return response()->json([
                'status' => 'ok',
                'next_token' => $queue->cur_token
            ], 200); 
        }
        else
        {
            return response()->json([
                'status' => 'end'
            ], 201); 
        }
    }

    public function deleteQueue()
    {
        $u = User::where('id', 52)->first();
        foreach ($this->user->queue->holders as $holder) {
            $holder->delete();
        }
        $this->user->queue->delete();
        return response()->json([
                'status' => 'ok'
            ], 200); 
    }

    public function checkQueue()
    {

        if($this->user->holder && $this->user->holder->token > $this->user->holder->queue->cur_token)  //check if user already standing in queue
        {
            return response()->json([
                'status' => 'inqueue',
                'qr' => $this->user->holder->queue->data
            ], 200);
        }

        //check if the users queue is currently running
        if($this->user->queue)
        {
            $max_token = $this->user->queue->holders()->max('token');
            if($this->user->queue->cur_token < $max_token)
            {
                return response()->json([
                    'status' => 'runningqueue'
                ], 200);
            }
        }

        return response()->json([
                'status' => 'ok'
            ], 200); 
    }

    public function sendPushNotification($data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
    }
}