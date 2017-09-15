<?php

namespace App\Api\V1\Controllers;

use Config;
use Log;
use Validator;
use App\User;
use App\Contact;
use App\Tag;
use Illuminate\Http\Request;
use App\Http\Requests;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContactController extends Controller
{
    protected $user;

    public function __construct(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        $this->middleware('cors');
        try {
            if (! $this->user = $JWTAuth->parseToken()->authenticate()) {
                Log::info('aaaaaa');
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
             return response()->json(['token_expired'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {Log::info('aaaaaa');
            return response()->json(['token_invalid'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            Log::info('aaaaaa');
            return response()->json(['token_absent'], 400);
        }
    }

    public function index()
    {
        $contacts = $this->user->contacts()->with('tags')->get();
        Log::info($contacts);
        return response()->json([
            'status' => 'ok',
            'contacts' => $contacts
        ], 200);
    }

    public function show($id)
    {
        $contact = Contact::whereId($id)->with('tags')->first();
        Log::info($contact);
        if($contact)
        {
            return response()->json([
                'status' => 'ok',
                'contact' => $contact
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 'fail',
                'message' => 'Contact not found'
            ], 400);
        }
        
    }

    public function update($id, Request $request)
    {
        $upd_contact = $request->contact;
        $contact = Contact::whereId($id)->with('tags')->first();
        $contact->name = $upd_contact['name'];
        $contact->email = $upd_contact['email'];
        $contact->job_title = $upd_contact['job_title'];
        $contact->location = $upd_contact['location'];

        $contact->tags()->detach();
        foreach ($upd_contact['tags'] as $tag) {
            $tag_id = Tag::whereName($tag['name'])->value('id');
            $contact->tags()->syncWithoutDetaching([$tag_id]);
        }
        if($contact->save())
        {
            return response()->json([
                'status' => 'ok'
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 'fail',
                'message' => 'Contact save error'
            ], 400);
        }
        
    }

    public function store(Request $request)
    {
        $new_contact = $request->contact;
        $contact = new Contact;
        Log::info($new_contact);
        $contact->user_id = $this->user->id;
        $contact->name = $new_contact['name'];
        $contact->email = $new_contact['email'];
        $contact->job_title = $new_contact['job'];
        $contact->location = $new_contact['location'];

        if($contact->save())
        {
            foreach ($new_contact['tags'] as $tag) {
                $tag_id = Tag::whereName($tag['name'])->value('id');
                $contact->tags()->syncWithoutDetaching([$tag_id]);
            }
            return response()->json([
                'status' => 'ok',
                'contact' => $contact
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 'fail',
                'message' => 'Contact save error'
            ], 400);
        }
    }

    public function destroy($id)
    {
        $contact = $this->user->contacts()->where('id',$id)->first();
        Log::info($contact);
        $contact->tags()->detach();
        if($contact->delete())
        {
            return response()->json([
                'status' => 'ok'
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 'fail',
                'message' => 'Contact not deleted'
            ], 400);
        }
    }


    public function verifyToken(JWTAuth $JWTAuth)
    {
        $user = $this->user;
        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }
}
