<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateField extends Model
{
    protected $fillable = ['template_id', 'field_name', 'pos_x', 'pos_y'];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
