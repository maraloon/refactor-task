<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoyaltyPointsWithdrawRequest extends FormRequest
{
    public function authorize()
    {
        return false;
    }

    public function rules()
    {
        return [
            'account_type' => ['required', Rule::in(['phone', 'card', 'email'])],
            'account_id' => 'required',
            'loyalty_points_rule' => 'exists:loyalty_points_rule,points_rule',
            'description' => 'required|string',
            'payment_id' => 'string',
            'payment_amount' => 'number',
            'payment_time' => 'integer',
        ];
    }
}
