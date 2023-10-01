<?php

namespace App\Http\Controllers;

use App\Core\CustomResponse;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Rules\CustomRulesOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function listProduct(Request $request)
    {
        try {
            $products = Product::where('state', 1)->get();
            if ($products) {
                return CustomResponse::success("datos encontrados", $products);
            } else {
                return CustomResponse::failure("no hay productos registrados");
            }
        } catch (\Throwable $th) {
            return CustomResponse::failure($th->getMessage());
        }
    }

    public function listOrder(Request $request)
    {
        try {
            $order = Order::where('state', 1)->get();
            if ($order) {
                return CustomResponse::success("datos encontrados", $order);
            } else {
                return CustomResponse::failure("no hay ordenes registrados");
            }
        } catch (\Throwable $th) {
            return CustomResponse::failure($th->getMessage());
        }
    }

    public function cancelOrder($order_id)
    {
        try {
            //estado 2 es cancelado
            //estado 3 es recepcionado

            $order = Order::where(['id' => $order_id, 'state' => 1])->update([
                'state' => 2
            ]);
            if ($order) return CustomResponse::success("Orden Cancelada");

            return CustomResponse::failure("Error al cancelar la orden");
        } catch (\Throwable $th) {
            return CustomResponse::failure($th->getMessage());
        }
    }

    public function registerOrder(Request $request)
    {
        $user = $request->input("user_id");
        $order = $request->input("type_orders_id");
        $details = $request->input("details_order");
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'type_orders_id' => 'required|numeric',
            'details_order' => ['required', new CustomRulesOrders],
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {
                // Iniciar una transacción
                DB::beginTransaction();
                $order = Order::create([
                    'user_id' => $user,
                    'type_orders_id' => $order
                ])->id;
                // Crear la orden y registrar los productos como se describió anteriormente
                foreach ($details as $key => $value) {
                    $order_details = OrderDetails::insert([
                        'order_id' => $order,
                        'product_id' => $value["product_id"],
                        'amount' => $value["amount"],
                    ]);

                    if (!$order_details) {
                        DB::rollback();
                        return CustomResponse::failure('Error al registrar el detalle del orden');
                    }
                }
                DB::commit();
                return CustomResponse::success('Orden registrado correctamente');
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
                DB::rollback();
            }
        }
    }

    public function updateOrder(Request $request)
    {
        $order_id = $request->input("order_id");
        $user = $request->input("user_id");
        $order = $request->input("type_orders_id");
        $details = $request->input("details_order");
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'type_orders_id' => 'required|numeric',
            'details_order' => ['required', new CustomRulesOrders],
        ]);
        if ($validator->fails()) {
            return CustomResponse::failure($validator->errors()->first());
        } else {

            try {
                // Iniciar una transacción
                DB::beginTransaction();
                $order = Order::where('id', $order_id)->update([
                    'user_id' => $user,
                    'type_orders_id' => $order
                ]);

                $orderDetailsDeleted = OrderDetails::where('order_id', $order_id)->delete();

                foreach ($details as $key => $value) {
                    $order_details = OrderDetails::insert([
                        'order_id' => $order_id,
                        'product_id' => $value["product_id"],
                        'amount' => $value["amount"],
                    ]);

                    if (!$order_details) {
                        DB::rollback();
                        return CustomResponse::failure('Error al editar el detalle del orden');
                    }
                }
                DB::commit();
                return CustomResponse::success('Orden modificado correctamente');
            } catch (\Throwable $th) {
                return CustomResponse::failure($th->getMessage());
                DB::rollback();
            }
        }
    }
}
