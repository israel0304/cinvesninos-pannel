<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'is_variable'];

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
}
