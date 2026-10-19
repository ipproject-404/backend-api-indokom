<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::take(5)->get(); 

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar 5 user pertama berhasil diambil',
            'data' => $users
        ], 200);
    }
}