<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use HasFactory;

    // Tambahkan baris ini jika nama tabelmu BUKAN 'presensis'
    protected $table = 'presensi'; 
}