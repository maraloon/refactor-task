<?php

namespace App\Repositories;

use App\Models\LoyaltyPointsRule;
use App\Models\LoyaltyPointsTransaction;

class LoyaltyPointsTransactionRepository
{
    public function performPaymentLoyaltyPoints(string $account_id, array $data): LoyaltyPointsTransaction
    {
        $pointsRule = LoyaltyPointsRule::query()
            ->where('points_rule', $data['points_rule'])
            ->firstOrFail();

        $points_amount = match ($pointsRule->accrual_type) {
            LoyaltyPointsRule::ACCRUAL_TYPE_RELATIVE_RATE => ($data['payment_amount'] / 100) * $pointsRule->accrual_value,
            LoyaltyPointsRule::ACCRUAL_TYPE_ABSOLUTE_POINTS_AMOUNT => $pointsRule->accrual_value
        };

        return LoyaltyPointsTransaction::create([
            'account_id' => $account_id,
            'points_rule' => $pointsRule->id,
            'points_amount' => $points_amount,
            'description' => $data['description'],
            'payment_id' => $data['payment_id'],
            'payment_amount' => $data['payment_amount'],
            'payment_time' => $data['payment_time'],
        ]);
    }

    public function withdrawLoyaltyPoints($account_id, $points_amount, $description)
    {
        return LoyaltyPointsTransaction::create([
            'account_id' => $account_id,
            'points_rule' => 'withdraw',
            'points_amount' => -$points_amount,
            'description' => $description,
        ]);
    }
}
