<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyPointsDepositRequest;
use App\Http\Requests\LoyaltyPointsWithdrawRequest;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyPointsTransaction;
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

    public function cancel()
    {
        $data = $_POST;

        $reason = $data['cancellation_reason'];

        if ($reason == '') {
            return response()->json(['message' => 'Cancellation reason is not specified'], 400);
        }

        if ($transaction = LoyaltyPointsTransaction::where('id', '=', $data['transaction_id'])->where('canceled', '=', 0)->first()) {
            $transaction->canceled = time();
            $transaction->cancellation_reason = $reason;
            $transaction->save();
        } else {
            return response()->json(['message' => 'Transaction is not found'], 400);
        }
    }

    public function withdraw(
        LoyaltyPointsWithdrawRequest $request,
        LoyaltyPointsTransactionRepository $repo
    ): JsonResponse {

        Log::info('Withdraw loyalty points transaction input: ' . print_r($request->toArray(), true));

        $account = $this->account($request);

        $points_amount = $request->request('points_amount');
        if ($request->request('points_amount') <= 0) {
            Log::info('Wrong loyalty points amount: ' . $points_amount);
            return response()->json(['message' => 'Wrong loyalty points amount'], 400);
        }

        if ($account->getBalance() < $points_amount) {
            Log::info('Insufficient funds: ' . $points_amount);
            return response()->json(['message' => 'Insufficient funds'], 400);
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
                Log::info('Account is not found');
                return response()->json(['message' => 'Account is not found'], 400);
            });

        if (!$account->active) {
            Log::info('Account is not active');
            return response()->json(['message' => 'Account is not active'], 400);
        }

        return $account;
    }
}
