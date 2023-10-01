<?php

namespace App\Http\Controllers;

use App\Core\CustomResponse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function registerProduct(Request $request)
    {
        $name = $request->input("name");
        $unit = $request->input("unit");
        $price = $request->input("price");
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'name')
            ],
            'unit' => 'required|string',
            'price' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {

                $product = Product::create([
                    'name' => $name,
                    'unit' => $unit,
                    'price' => $price,
                ]);
                if ($product) return  CustomResponse::success('Producto Creado Correctamente', $product);
                return  CustomResponse::failure('Hubo un problema al crear un producto');
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
            }
        }
    }

    public function updateProduct(Request $request)
    {
        $id = $request->input("product_id");
        $name = $request->input("name");
        $unit = $request->input("unit");
        $price = $request->input("price");
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric',
            'name' => [
                'required',
                'string',
                'max:50',
            ],
            'unit' => 'required|string',
            'price' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {

                $product = Product::where('id', $id)->update([
                    'name' => $name,
                    'unit' => $unit,
                    'price' => $price,
                ]);
                if ($product) return  CustomResponse::success('Producto Modificado Correctamente');
                return  CustomResponse::failure('Hubo un problema al crear un producto');
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
            }
        }
    }

    public function udpateState(Request $request)
    {
        $id = $request->input("id");
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {

                $product = Product::find($id);
                if (!$product) return  CustomResponse::failure('No se encontro el producto');
                if ($product->state == 1) {
                    $product->state = 0;
                    $product->save();
                    return CustomResponse::success("El producto $product->name a sido desactivado");
                } else {
                    $product->state = 1;
                    $product->save();
                    return CustomResponse::success("El producto $product->name a sido activado");
                }
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
            }
        }
    }

    public function listProduct(Request $request)
    {
        try {
            $products = Product::all();

            if ($products) {
                return CustomResponse::success("datos encontrados", $products);
            } else {
                return CustomResponse::failure("no hay productos registrados");
            }
        } catch (\Throwable $th) {
            return CustomResponse::failure($th->getMessage());
        }
    }
}
