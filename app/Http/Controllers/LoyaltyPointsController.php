<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyPointsDepositRequest;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyPointsTransaction;
use App\Repositories\LoyaltyPointsTransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LoyaltyPointsController extends Controller
{
    public function deposit(
        LoyaltyPointsDepositRequest $request,
        LoyaltyPointsTransactionRepository $repo
    ): JsonResponse {

        Log::info('Deposit transaction input: ' . print_r($request->toArray(), true));

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

    public function withdraw(LoyaltyPointsTransactionRepository $repo)
    {
        $data = $_POST;

        Log::info('Withdraw loyalty points transaction input: ' . print_r($data, true));

        $type = $data['account_type'];
        $id = $data['account_id'];
        if (($type == 'phone' || $type == 'card' || $type == 'email') && $id != '') {
            if ($account = LoyaltyAccount::where($type, '=', $id)->first()) {
                if ($account->active) {
                    if ($data['points_amount'] <= 0) {
                        Log::info('Wrong loyalty points amount: ' . $data['points_amount']);
                        return response()->json(['message' => 'Wrong loyalty points amount'], 400);
                    }
                    if ($account->getBalance() < $data['points_amount']) {
                        Log::info('Insufficient funds: ' . $data['points_amount']);
                        return response()->json(['message' => 'Insufficient funds'], 400);
                    }

                    $transaction = $repo->withdrawLoyaltyPoints($account->id, $data['points_amount'], $data['description']);
                    Log::info($transaction);
                    return $transaction;
                } else {
                    Log::info('Account is not active: ' . $type . ' ' . $id);
                    return response()->json(['message' => 'Account is not active'], 400);
                }
            } else {
                Log::info('Account is not found:' . $type . ' ' . $id);
                return response()->json(['message' => 'Account is not found'], 400);
            }
        } else {
            Log::info('Wrong account parameters');
            throw new \InvalidArgumentException('Wrong account parameters');
        }
    }
}
