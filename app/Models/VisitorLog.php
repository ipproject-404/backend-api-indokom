<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    // TAMBAHKAN BARIS INI
    protected $fillable = [
        'hashed_id',
        'request_count',
        'last_seen',
    ];
}