<?php
namespace App\Http\Controllers;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayboxController
{
    public function success(Request $request)
    {
        $userId = $request->get('user_id');
        $user   = User::where('id', $userId)->first();
        $tariff = Tariff::where('id', $request->get('tarif_id'))->first();

        $user->revival_count = $user->revival_count + $tariff->revival_count;
        $user->tariff_id     = $tariff->id;
        $user->save();

        $orderId             = $request->get('order_id');
        $transaction         = Transaction::where('id', $orderId)->first();
        $transaction->status = 1; // Success
        $transaction->save();

        return redirect('https://pro.arstudio.kz/profile?status=success');
    }

    public function failure(Request $request)
    {
        Log::error("Произошда ошибка при оплате пейбокс на сайте");

        $orderId             = $request->get('order_id');
        $transaction         = Transaction::where('id', $orderId)->first();
        $transaction->status = 2; // failure
        $transaction->save();

        return redirect('https://pro.arstudio.kz/profile?status=fail');
    }
}
