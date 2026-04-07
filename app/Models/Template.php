<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['name', 'background_path'];

    public function fields()
    {
        return $this->hasMany(TemplateField::class);
    }
}
