<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Constants\HttpStatusCodes;
use Auth;
use Validator;

use App\Models\User;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'type' => 'customer'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function login(Request $request)
    {
        $customMessages = [
            'email.exists' => 'Pengguna tidak ditemukan',
            'email.required' => 'Email penguna tidak boleh kosong',
            'password.required' => 'Kata sandi tidak boleh kosong',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email,deleted_at,NULL',
            'password' => 'required|string',
            
        ], $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status_code'   => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'         => true,
                'message'       => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
                'status_code'   => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'         => true,
                'message' => 'Kata sandi salah',
                ], 401);
        }

        $user = User::select(
                'id',
                'name',
                'email',
                'type',
            )
            ->where('email', $request['email'])
            ->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
                'status_code'   => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'         => false,
                'data'          => [
                    'access_token'  => $token,
                    'token_type'    => 'Bearer',
                    'user'          => $user
                ]
        ]);
    }

}
