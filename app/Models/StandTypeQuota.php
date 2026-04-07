<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandTypeQuota extends Model
{
    protected $fillable = ['stand_type_id', 'product_id', 'limit_quantity', 'setting_id'];

    public function standType()
    {
        return $this->belongsTo(StandType::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
