<?php

namespace App\Services\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayboxStatusPaymentRequest  extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pg_order_id' => ['string', 'required'],
            'pg_merchant_id' => ['numeric', 'required'],
            'pg_salt' => ['required', 'string'],
        ];
    }
}
