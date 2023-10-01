<?php

namespace App\Core;

use Illuminate\Http\JsonResponse;


class CustomResponse
{
    static public function success($message = 'Petición exitosa', $data = null, $code = 200): JsonResponse
    {
        if ($data) {

            return response()->json([
                'status' => true,
                'code' => $code,
                'message' => $message,
                'data' => $data
            ]);
        } else {
            return response()->json([
                'status' => true,
                'code' => $code,
                'message' => $message,
                'data' => true
            ]);
        }
    }

    static public function failure($message = 'Ocurrió un error',$data=null, $code = 400): JsonResponse
    {

        if ($data) {

            return response()->json([
                'status' => false,
                'code' => $code,
                'message' => $message,
                'error' => $data
            ]);
        } else {

            return response()->json([
                'status' => false,
                'code' => $code,
                'message' => $message,
            ]);
        }
    }
}
