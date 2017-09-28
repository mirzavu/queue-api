<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Holder extends Model
{

	protected $fillable = [
        'user_id', 'queue_id', 'token'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function queue()
    {
        return $this->belongsTo('App\Queue');
    }
}