<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankLetter extends Model
{
    protected $fillable = ['details'];
    protected $casts = ['details' => 'array'];
}
