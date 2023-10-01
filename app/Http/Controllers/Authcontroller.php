<?php

namespace App\Http\Controllers;

use App\Core\CustomResponse; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Authcontroller extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input("email");
        $password = $request->input("password");
        // $credential = $request->only(['email', 'password']);
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required', 
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure("Datos Faltantes");
        } else {

            try {

                $parametros = array(
                    'email' => $email,
                    'password' => $password
                );

                if (!$token = auth()->attempt($parametros)) {
                    abort(401, 'Unauthorized');
                }
                $user = $request->user();
                // $user = User::where(['email'=>$email,'password'=>bcrypt($password)])->first();
                $data = [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth()->factory()->getTTL() * 100,
                    'user'=>$user
                ];
                return  CustomResponse::success('Login correcto', $data); 

            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage()); 
            }
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            // Obtén el token actual del usuario autenticado
            $token = auth()->refresh();
            $user = $request->user();
            $data = [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user'=>$user
            ];
            return  CustomResponse::success('Token renovado', $data);
        } catch (\Throwable $th) {
            return CustomResponse::failure($th->getMessage());
        }
    }
}
