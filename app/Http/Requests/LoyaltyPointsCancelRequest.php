<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyPointsCancelRequest extends FormRequest
{
    public function authorize()
    {
        return false;
    }

    public function rules()
    {
        return [
            'cancellation_reason' => 'required|string|filled',
            'transaction_id' => 'required|exists:loyalty_points_transaction',
        ];
    }
}
