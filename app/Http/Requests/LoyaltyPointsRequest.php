<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoyaltyPointsRequest extends FormRequest
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
        ];
    }
}
