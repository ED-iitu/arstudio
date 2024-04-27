<?php

namespace App\Services\Payment;

use App\Services\Payment\Requests\NewPaymentPayboxRequest;
use App\Services\Payment\Requests\PayboxStatusPaymentRequest;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

class PayboxService
{
    public $status;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Создание нового платежа
     *
     * @param array $data
     * @param string $operation
     * @return SimpleXMLElement
     *
     * @throws Exception
     */
    public function generate(array $data, string $operation): SimpleXMLElement
    {
        $validator = Validator::make(
            $data = $this->resolveData($data, $operation),
            (new NewPaymentPayboxRequest())->rules()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->generateSig($data, 'init_payment.php');

        return $this->request('post', $this->fullPath('init_payment'), $data);
    }

    /**
     * Получение информации о платеже
     *
     * @param array $data
     *
     * @return PayboxService
     *
     * @throws Exception
     */
    public function paymentInfo(array $data)
    {
        $validator = Validator::make(
            $this->resolveData($data),
            (new PayboxStatusPaymentRequest())->rules()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->generateSig($data, 'get_status2.php');
        $req = $this->request('get', $this->fullPath('status_payment'), $data);
        $this->setStatus(new PayboxStatus());
        $this->status->setPgStatus($req->pg_status);
        $this->status->setPgPaymentId($req->pg_payment_id);
        $this->status->setPgTransactionStatus($req->pg_transaction_status);

        return $this;
    }

    /**
     * @param $route
     * @return string
     */
    private function fullPath($route): string
    {
        return $this->config['url'] . '/' . $this->config['routes'][$route];
    }

    /**
     * @throws Exception
     */
    private function request(string $verb, string $route, array $data): SimpleXMLElement
    {
        $v = strtolower($verb);
        $response = Http::{$v}($route, $data);
        if (!$response->ok()) {
            throw new Exception($response->body());
        }

        $data = simplexml_load_string($response->body());

        if ($data->pg_status != 'ok') {
            throw new Exception($response->body());
        }

        return $data;
    }

    /**
     * @return PayboxStatus
     */
    public function getStatus(): PayboxStatus
    {
        return $this->status;
    }

    /**
     * @param PayboxStatus $status
     */
    public function setStatus(PayboxStatus $status): void
    {
        $this->status = $status;
    }


    /**
     * @param array $data
     * @param string $type
     * @return void
     */
    private function generateSig(array &$data, string $type)
    {
        $requestForSignature = $this->makeFlatParamsArray($data);
        ksort($requestForSignature);
        array_unshift($requestForSignature, $type);
        $requestForSignature[] = $this->config['secret_key'];
        $data['pg_sig'] = md5(implode(';', $requestForSignature));
    }

    /**
     * Имя делаем вида tag001subtag001
     * Чтобы можно было потом нормально отсортировать и вложенные узлы не запутались при сортировке
     */
    private function makeFlatParamsArray($arrParams, $parent_name = ''): array
    {
        $arrFlatParams = [];
        $i = 0;
        foreach ($arrParams as $key => $val) {
            $i++;

            $name = $parent_name . $key . sprintf('%03d', $i);
            if (is_array($val)) {
                $arrFlatParams = array_merge($arrFlatParams, $this->makeFlatParamsArray($val, $name));
                continue;
            }
            $arrFlatParams += array($name => (string) $val);
        }

        return $arrFlatParams;
    }

    public function resolveData(array $data, ?string $operation = null): array
    {
        return array_merge($data, [
            'pg_merchant_id' => $this->config['merchant_id'],
            'pg_salt' => $this->config['salt'],
            'pg_success_url' => $this->config['success_callback'] . "?tarif_id=" . $data['tarif_id'] . "&amount=" . $data['pg_amount'] . "&user_id=" . $data['user_id'] . "&order_id=" . $data['order_id'],
            'pg_success_url_method' => 'GET',
            'pg_failure_url' => $this->config['failure_callback'] . "?tarif_id=" . $data['tarif_id'] . "&order_id=" . $data['order_id'],
            'pg_failure_url_method' => 'GET',
        ]);
    }
}
