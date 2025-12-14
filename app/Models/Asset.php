<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'symbol', 'amount', 'locked_amount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
