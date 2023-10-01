<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CustomRulesOrders implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_array($item) || count($item) === 0) {
                return false;
            }
 
            if (!isset($item['product_id']) || !isset($item['amount'])) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'El campo :attribute debe ser un array de objetos con las propiedades "product_id" y "amount".';
        // return 'The :attribute must be an array of objects with "product_id" and "amount" fields.';
    }
}
