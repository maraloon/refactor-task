<?php

namespace App\Providers;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Response::macro('error', function ($message, int $status = HttpResponse::HTTP_BAD_REQUEST) {
            Log::info($message);
            return response()->json([
                'message' => $message,
            ], $status);
        });
    }
}
