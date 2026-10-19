<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        // 2. Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // 3. Cek apakah user ada DAN password-nya cocok
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        // 4. Buat Token (sesuaikan nama tokennya)
        $token = $user->createToken('token-aplikasi')->plainTextToken;

        // 5. Kembalikan response JSON beserta tokennya
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ], 200);
    }
}