<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payment\PayboxService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function purchase(Request $request)
    {
        $orderId        = $this->getTransactionId($request);
        $payBoxService  = new PayboxService(config('paybox'));
        $payboxResponse = (array)$payBoxService->generate([
            'tarif_id'       => $request->get('tarif_id') ?? 1,
            'user_id'        => $request->get('user_id') ?? 1,
            'pg_order_id'    => (string)$orderId,
            'order_id'       => $orderId,
            'pg_amount'      => $request->get('amount') ?? 100,
            'pg_description' => 'Оплата заказа №' . $orderId,
        ], 'purchase');

        return $payboxResponse['pg_redirect_url'];
    }

    protected function getTransactionId(Request $request): int
    {
        $transaction = new Transaction();
        $userId      = $request->get('user_id') ?? 1;
        $tarifId     = $request->get('tarif_id') ?? 1;

        $transaction->user_id     = $userId;
        $transaction->tarif_id    = $tarifId;
        $transaction->description = 'Покупка тарифа №' . $tarifId;
        $transaction->amount      = $request->get('amount') ?? 100;

        $transaction->save();

        return $transaction->id;
    }
}
