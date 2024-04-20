<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyPointsCancelRequest;
use App\Http\Requests\LoyaltyPointsDepositRequest;
use App\Http\Requests\LoyaltyPointsWithdrawRequest;
use App\Models\LoyaltyAccount;
use App\Repositories\LoyaltyPointsTransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoyaltyPointsController extends Controller
{
    public function deposit(
        LoyaltyPointsDepositRequest $request,
        LoyaltyPointsTransactionRepository $repo
    ): JsonResponse {

        Log::info('Deposit transaction input: ' . print_r($request->toArray(), true));

        $account = $this->account($request);

        $transaction = $repo->performPaymentLoyaltyPoints(
            $account->id,
            $request->only(['loyalty_points_rule', 'description', 'payment_id', 'payment_amount', 'payment_time']),
        );
        Log::info($transaction);

        $account->notifyPointsReceived($transaction->points_amount);

        return response()->json($transaction);
    }

    public function cancel(
        LoyaltyPointsCancelRequest $request,
        LoyaltyPointsTransactionRepository $repo
    ): JsonResponse {
        $transaction = $repo->cancelTransaction(
            $request->request('transaction_id'),
            $request->request('cancellation_reason')
        );

        return response()->json($transaction);
    }

    public function withdraw(
        LoyaltyPointsWithdrawRequest $request,
        LoyaltyPointsTransactionRepository $repo
    ): JsonResponse {

        Log::info('Withdraw loyalty points transaction input: ' . print_r($request->toArray(), true));

        $account = $this->account($request);

        $points_amount = $request->request('points_amount');
        if ($points_amount <= 0) {
            return response()->error('Wrong loyalty points amount: ' . $points_amount);
        }

        if ($account->getBalance() < $points_amount) {
            return response()->error('Insufficient funds: ' . $points_amount);
        }

        $transaction = $repo->withdrawLoyaltyPoints(
            $account->id,
            $points_amount,
            $request->request('description')
        );
        Log::info($transaction);

        return response()->json($transaction);
    }

    protected function account(Request $request): LoyaltyAccount
    {
        $type = $request->request('account_type');
        $id = $request->request('account_id');

        $account = LoyaltyAccount::query()
            ->where($type, $id)
            ->firstOr(function () {
                return response()->error('Account is not found');
            });

        if (!$account->active) {
            return response()->error('Account is not active');
        }

        return $account;
    }
}
