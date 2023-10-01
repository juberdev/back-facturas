<?php

namespace App\Http\Controllers;

use App\Core\CustomResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function registerUser(Request $request)
    {
        $name = $request->input("name");
        $email = $request->input("email");
        $store_id = $request->input("store_id");
        $rol_id = $request->input("rol_id");
        $password = $request->input("password");
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'string',
                'max:250', Rule::unique('users', 'email')
            ],
            'name' => [
                'required',
                'string',
                'max:50', Rule::unique('users', 'name')
            ],
            'store_id' => 'required|numeric',
            'rol_id' => 'required|numeric',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {

                $user = User::create([
                    'name' =>$name,
                    'email' => $email,
                    'password' => bcrypt($password),
                    'store_id' => $store_id, 
                    'rol_id' => $rol_id,
                ]);
                if ($user) return  CustomResponse::success("Usuario $name creado Correctamente");
                return  CustomResponse::failure('Hubo un problema al registrar un usuario');
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
            }
        }
    }
}
