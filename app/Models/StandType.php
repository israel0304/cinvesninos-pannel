<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandType extends Model
{
    protected $fillable = ['name', 'description'];

    public function stands()
    {
        return $this->hasMany(Stand::class);
    }
}
