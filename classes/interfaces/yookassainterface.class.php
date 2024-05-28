<?php
/**
 * Файл класса интерфейса электронной оплаты через ЮКаssа
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\Exception;
use RAAS\Redirector;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс интерфейса электронной оплаты через ЮКаssа
 */
class YooKassaInterface extends EPayInterface
{
    const EPAY_URN = 'yookassa';

    const BANK_NAME = 'ЮКаssа';

    const REQUEST_STATUS_PAID = 'succeeded';

    /**
     * Ставка НДС - без НДС
     */
    const TAX_TYPE_NO_VAT = 1;

    /**
     * Система налогообложения - упрощенная, доход
     */
    const TAX_SYSTEM_SIMPLE = 2;

    /**
     * Предмет расчета - товар
     */
    const PAYMENT_SUBJECT_COMMODITY = 'commodity';

    /**
     * Признак способа расчета - Полная предоплата
     */
    const PAYMENT_METHOD_FULL_PREPAYMENT = 'full_prepayment';

    public function findOrder()
    {
        if ($orderId = $this->get['orderId'] ?? null) { // Возврат на страницу (передавали в return_url)
            $order = new Order($orderId);
            if ($order->id) {
                return $order;
            }
        }
        return parent::findOrder();
    }


    public function checkWebhook(string $debugInput = null)
    {
        if (($this->server['REQUEST_METHOD'] ?? '') == 'POST') {
            $request = $this->post['@raw'] ?? file_get_contents('php://input'); // $this->post['@raw'] для проверки
            if (substr($request, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $request = substr($request, 3);
            }
            $json = @json_decode($request, true);
            if ($json) {
                if ($paymentId = $json['object']['id'] ?? null) {
                    $result = ['paymentId' => $paymentId];
                    return $result;
                }
            }
        }
        return null;
    }


    public function exec(string $method, array $requestData = [], bool $isTest = false): array
    {
        $login = $password = '';
        if ($this->block) {
            $login = (string)$this->block->epay_login;
            $password = (string)$this->block->epay_pass1;
        }
        $idempotencyKey = crc32(md5($password) . $method . json_encode($requestData));
        $headers = [
            'Idempotence-Key: ' . $idempotencyKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($login . ':' . $password),
        ];
        $url = $this->getURL($isTest) . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($requestData) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        $request = '';
        if ($requestData) {
            $request = json_encode($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        $result = curl_exec($ch);
        if ($isTest) {
            $this->doLogRequest($url, $request, $result);
        }
        $json = @json_decode($result, true);
        return $json;
    }



    public function getURL(bool $isTest = false): string
    {
        $url = 'https://api.yookassa.ru/v3/';
        return $url;
    }


    /**
     * Получение данные для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array
     */
    public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
    {
        $currency = $block->epay_currency;
        if (($currency == 'RUR') || !$currency) {
            $currency = 'RUB';
        }

        $cartData = [];
        $positiveItemsData = $this->getPositiveItems($order);
        $positiveItems = $positiveItemsData['items'];
        $totalSumKop = $positiveItemsData['sum'];
        foreach ($positiveItems as $i => $item) {
            $itemData = [
                'description' => $item->name,
                'amount' => [
                    'value' => (float)($item->epayPriceKop / 100),
                    'currency' => $currency,
                ],
                'vat_code' => $order->taxType ?: static::TAX_TYPE_NO_VAT,
                'quantity' => (float)$item->amount,
                'payment_subject' => $order->paymentSubject ?: static::PAYMENT_SUBJECT_COMMODITY,
                'payment_mode' => $order->paymentMethod ?: static::PAYMENT_METHOD_FULL_PREPAYMENT,
            ];
            // if (trim((string)$item->yookassa_unit)) {
            //     $itemData['measure'] = trim((string)$item->yookassa_unit);
            // }
            $cartData[] = $itemData;
        }


        $requestData = [
            'amount' => [
                'value' => (float)($totalSumKop / 100),
                'currency' => $currency,
            ],
            'description' => $this->getOrderDescription($order),
            'receipt' => [
                'customer' => [],
                'items' => $cartData,
                'tax_system_code' => trim((string)$order->taxSystem) ?: static::TAX_SYSTEM_SIMPLE,
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->getCurrentHostURL() . $page->url . 'result/?orderId=' . (int)$order->id,
                // 2024-05-01, AVS: убрал additionalURL (может запутать)
                // По документации:
                // 1. Можно передавать GET-параметры
                // https://yookassa.ru/developers/payment-acceptance/getting-started/quick-start
                // 2. URL для уведомлений для Basic Auth настраивается в личном кабинете
                // https://yookassa.ru/developers/using-api/webhooks
            ],
            'capture' => true,
            'metadata' => [
                'order_id' => (string)(int)$order->id,
            ],
        ];
        $fullNameArr = [];
        if (trim((string)$order->full_name)) {
            $fullNameArr[] = trim((string)$order->full_name);
        } else {
            foreach (['full_name', 'last_name', 'first_name', 'second_name'] as $fieldURN) {
                if (trim((string)$order->$fieldURN)) {
                    $fullNameArr[] = trim((string)$order->$fieldURN);
                }
            }
        }
        $fullNameArr = array_values(array_filter($fullNameArr));
        if ($fullNameArr) {
            $requestData['receipt']['customer']['full_name'] = implode(' ', $fullNameArr);
        }
        if (trim((string)$order->inn)) {
            $requestData['receipt']['customer']['inn'] = trim((string)$order->inn);
        }
        if (trim((string)$order->email) !== '') {
            $requestData['receipt']['customer']['email'] = trim((string)$order->email);
        }
        if (trim((string)$order->phone) !== '') {
            $requestData['receipt']['customer']['phone'] = Text::beautifyPhone($order->phone, 11);
        }

        return $requestData;
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $response = $this->exec('payments', $data, (bool)$block->epay_test);
        return $response;
    }


    public function parseResponseCommonErrors(array $response): array
    {
        $result = [];
        if ((($response['type'] ?? null) == 'error') && ($response['description'] ?? '')) {
            $errorData = ['message' => $response['description']];
            if ($response['code'] ?? null) {
                $errorData['code'] = (string)$response['code'];
            }
            $result[] = $errorData;
        }
        return $result;
    }


    public function parseInitResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['id'] ?? null) && ($response['confirmation']['confirmation_url'] ?? null)) {
            $result['paymentId'] = $response['id'];
            $result['paymentURL'] = $response['confirmation']['confirmation_url'];
        }
        return $result;
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $result = $this->exec('payments/' . $order->payment_id, [], (bool)$block->epay_test);
        return $result;
    }


    public function parseOrderStatusResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['id'] ?? null) && ($response['status'] ?? null)) {
            $result['status'] = $response['status'];
        }
        return $result;
    }
}
