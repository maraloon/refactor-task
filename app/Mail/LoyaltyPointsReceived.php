<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoyaltyPointsReceived extends Mailable
{
    use Queueable, SerializesModels;

    private $balance;
    private $pointsAmount;

    public function __construct($pointsAmount, $balance)
    {
        $this->balance = $balance;
        $this->pointsAmount = $pointsAmount;
    }

    public function build()
    {
        return $this->view('emails.loyaltyPointsReceived')->with([
            'balance' => $this->balance,
            'points' => $this->pointsAmount,
        ]);
    }
}
