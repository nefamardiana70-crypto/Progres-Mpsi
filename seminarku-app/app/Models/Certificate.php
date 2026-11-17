<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
