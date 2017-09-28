<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
	protected $table = 'queue';

	protected $fillable = [
        'file', 'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function holders()
    {
        return $this->hasMany('App\Holder');
    }
}
